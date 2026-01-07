<?php

namespace VildanBina\LaravelAutoTranslation\Drivers;

use Exception;
use Illuminate\Support\Facades\Http;
use Throwable;
use TikToken\Encoder;
use VildanBina\LaravelAutoTranslation\Contracts\TranslationDriver;

class OllamaDriver implements TranslationDriver
{
    private const BUFFER_FACTOR = 2;

    private array $config;

    private Encoder $encoder;

    public function __construct(array $config)
    {
        $this->config = $config;
        $this->encoder = new Encoder;
    }

    private function estimateTokens(array $data, string $sourceLang, string $targetLang): int
    {
        try {
            return count($this->encoder->encode(
                json_encode($this->buildPrompt($data, $sourceLang, $targetLang))
            ));
        } catch (Throwable) {
            return PHP_INT_MAX;
        }
    }

    public function translate(array $texts, string $sourceLang, string $targetLang): array
    {
        $translations = [];
        $chunks = $this->makeChunks($texts, $sourceLang, $targetLang);

        collect($chunks)->each(function (array $value) use (&$translations, $sourceLang, $targetLang) {
            $chunkResult = $this->sendTranslationRequest($value, $sourceLang, $targetLang);
            $translations += is_array($chunkResult) ? $chunkResult : [];
        });

        return $translations;
    }

    private function makeChunks(array $texts, string $sourceLang, string $targetLang): array
    {
        $maxTokens = $this->config['max_tokens'] ?? 2048; // Local models often have higher defaults
        $usable = (int) floor($maxTokens / self::BUFFER_FACTOR);
        $chunks = [];
        $current = [];

        foreach ($texts as $key => $text) {
            $test = $current + [$key => $text];
            if ($this->estimateTokens($test, $sourceLang, $targetLang) > $usable) {
                if ($current) {
                    $chunks[] = $current;
                }
                $current = [$key => $text];
            } else {
                $current[$key] = $text;
            }
        }

        if ($current) {
            $chunks[] = $current;
        }

        return $chunks;
    }

    protected function buildPrompt(array $chunk, string $sourceLang, string $targetLang): array
    {
        return [
            [
                'role' => 'system',
                'content' => <<<EOL
You are a professional translator specializing in software localization.
Your task is to translate text from {$sourceLang} to {$targetLang}.

IMPORTANT INSTRUCTIONS:
- The input will always be a JSON object.
- Do NOT alter or translate any of the keys in the JSON object.
- Only translate the values associated with the keys.
- Do NOT alter tokens wrapped in %%...%% or placeholders like :attribute.
- Return ONLY a valid JSON object.
EOL
            ],
            [
                'role' => 'user',
                'content' => json_encode($chunk),
            ],
        ];
    }

    protected function sendTranslationRequest(array $texts, string $sourceLang, string $targetLang): array
    {
        // Defaulting to standard Ollama OpenAI-compatible endpoint
        $baseUrl = $this->config['api_url'] ?? 'http://localhost:11434/v1';
        
        $response = Http::baseUrl($baseUrl)
            ->withHeaders([
                'Accept' => 'application/json',
            ])
            ->timeout($this->config['http_timeout'] ?? 60) // Local LLMs need more time
            ->post('/chat/completions', [
                'model' => $this->config['model'] ?? 'llama3',
                'messages' => $this->buildPrompt($texts, $sourceLang, $targetLang),
                'temperature' => $this->config['temperature'] ?? 0,
                'response_format' => ['type' => 'json_object'],
                'stream' => false,
            ]);

        if (! $response->successful()) {
            $errorMessage = $response->json()['error']['message'] ?? 'Ollama connection error';
            throw new Exception('Ollama API error: ' . $errorMessage);
        }

        $content = $response->json()['choices'][0]['message']['content'] ?? '';

        if (empty($content) || ! json_validate($content)) {
            throw new Exception('Invalid JSON returned by Ollama.');
        }

        $decoded = json_decode($content, true);

        if (count($decoded) !== count($texts)) {
            throw new Exception('Mismatch in translation count from Ollama.');
        }

        return array_combine(array_keys($texts), $decoded);
    }
}