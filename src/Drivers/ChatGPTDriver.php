<?php

namespace VildanBina\LaravelAutoTranslation\Drivers;

use Exception;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Throwable;
use TikToken\Encoder;
use VildanBina\LaravelAutoTranslation\Contracts\TranslationDriver;

class ChatGPTDriver implements TranslationDriver
{
    private array $config;

    private Encoder $encoder;

    public function __construct(array $config)
    {
        $this->config = $config;
        $this->encoder = new Encoder;
    }

    protected function getChunkSize(array $texts, string $sourceLang, string $targetLang): float
    {
        $prompt = $this->buildPrompt($texts, $sourceLang, $targetLang);

        // Encode the prompt and calculate its length in tokens
        $encodedLength = count($this->encoder->encode(json_encode($prompt)));
        $maxTokens = $this->config['max_tokens'] ?? 1000;

        // Calculate the number of tokens each text contributes on average
        $tokensPerText = (int) round($encodedLength / $maxTokens);

        // Calculate the chunk size, ensuring additional buffer space by adding
        // 50% of the average tokens per text. This adjustment helps prevent
        // exceeding the token limit in the output.
        try {
            return ceil(count($texts) / ($tokensPerText + ($tokensPerText / 2)));
        } catch (Throwable $throwable) {
            return 1;
        }
    }

    public function translate(array $texts, string $sourceLang, string $targetLang): array
    {
        $translations = [];
        $currentChunk = [];

        collect($texts)
            ->chunk($this->getChunkSize($texts, $sourceLang, $targetLang))
            ->each(function (Collection $value) use (&$translations, $sourceLang, $targetLang) {
                $chunkResult = $this->sendTranslationRequest($value->toArray(), $sourceLang, $targetLang);
                $translations = array_merge($translations, $chunkResult);
            });

        if (! empty($currentChunk)) {
            $chunkResult = $this->sendTranslationRequest($currentChunk, $sourceLang, $targetLang);
            $translations = array_merge($translations, $chunkResult);
        }

        return $translations;
    }

    protected function buildPrompt(array $chunk, string $sourceLang, string $targetLang): array
    {
        return [
            [
                'role' => 'system',
                'content' => <<<EOL
You are a helpful assistant that translates text from {$sourceLang} to {$targetLang}.
    IMPORTANT INSTRUCTIONS:
    - The input will always be a JSON object.
    - Do NOT alter or translate any of the keys in the JSON object. Keys must remain exactly as provided.
    - Only translate the values associated with the keys.
    - Do NOT alter tokens wrapped in %%...%% or placeholders like :attribute.
    - Return the output as a valid JSON object where:
        - The keys remain unchanged.
        - The values are properly translated.
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
        $prompt = $this->buildPrompt($texts, $sourceLang, $targetLang);

        $response = Http::baseUrl('https://api.openai.com')
            ->withHeaders([
                'Authorization' => 'Bearer '.$this->config['api_key'],
            ])
            ->timeout($this->config['http_timeout'] ?? 30)
            ->post('/v1/chat/completions', [
                'model' => $this->config['model'] ?? 'gpt-3.5-turbo',
                'messages' => $prompt,
                'temperature' => $this->config['temperature'] ?? 0.7,
                'max_tokens' => $this->config['max_tokens'] ?? 1000,
            ]);

        if (! $response->successful()) {
            throw new Exception('ChatGPT API error: '.$response->json()['error']['message']);
        }

        $content = $response->json()['choices'][0]['message']['content'] ?? '';
        if (! json_validate($content)) {
            throw new Exception('Invalid JSON returned by ChatGPT: '.json_last_error_msg());
        }

        $decoded = json_decode($content, true);

        if (count($decoded) !== count($texts)) {
            throw new Exception('Mismatch in number of translated texts returned by ChatGPT.');
        }

        return array_combine(array_keys($texts), $decoded);
    }
}
