<?php

declare(strict_types=1);

namespace VildanBina\LaravelAutoTranslation\Drivers;

use Exception;
use Illuminate\Support\Facades\Http;
use VildanBina\LaravelAutoTranslation\Contracts\TranslationDriver;

final class ChatGPTDriver implements TranslationDriver
{
    private $config;

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    public function translate(array $texts, string $sourceLang, string $targetLang): array
    {
        $apiKey = $this->config['api_key'];
        $model  = $this->config['model'] ?? 'gpt-3.5-turbo';

        $prompt = $this->buildPrompt($texts, $sourceLang, $targetLang);

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $apiKey,
        ])->post('https://api.openai.com/v1/chat/completions', [
            'model'       => $model,
            'messages'    => $prompt,
            'temperature' => $this->config['temperature'] ?? 0.7,
            'max_tokens'  => $this->config['max_tokens'] ?? 1000,
        ]);

        if ( ! $response->successful()) {
            throw new Exception('ChatGPT API error: ' . $response->json()['error']['message']);
        }

        $translatedText  = $response->json()['choices'][0]['message']['content'];
        $translatedArray = explode("\n", trim($translatedText));

        if (count($translatedArray) !== count($texts)) {
            throw new Exception('Mismatch in number of translated texts returned by ChatGPT.');
        }

        return array_combine(array_keys($texts), $translatedArray);
    }

    private function buildPrompt(array $texts, string $sourceLang, string $targetLang): array
    {
        $textList = implode("\n", array_values($texts));

        return [
            [
                'role'    => 'system',
                'content' => "You are a helpful assistant that translates texts from {$sourceLang} to {$targetLang}.",
            ],
            [
                'role'    => 'user',
                'content' => $textList,
            ],
        ];
    }
}
