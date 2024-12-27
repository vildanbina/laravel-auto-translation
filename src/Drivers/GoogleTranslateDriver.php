<?php

namespace VildanBina\LaravelAutoTranslation\Drivers;

use Exception;
use Illuminate\Support\Facades\Http;
use VildanBina\LaravelAutoTranslation\Contracts\TranslationDriver;

class GoogleTranslateDriver implements TranslationDriver
{
    private $config;

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    public function translate(array $texts, string $sourceLang, string $targetLang): array
    {
        $apiKey = $this->config['api_key'];
        $joinedTexts = implode("\n", array_values($texts));
        $params = [
            'q' => $joinedTexts,
            'source' => $sourceLang,
            'target' => $targetLang,
            'key' => $apiKey,
            'format' => 'text',
        ];

        $response = Http::asForm()->post('https://translation.googleapis.com/language/translate/v2', $params);
        if (!$response->successful()) {
            $error = $response->json()['error']['message'] ?? $response->body();
            throw new Exception('Google Translate API error: ' . $error);
        }

        $translatedArray = explode("\n", $response->json()['data']['translations'][0]['translatedText']);
        if (count($translatedArray) !== count($texts)) {
            throw new Exception('Mismatch in number of translated texts returned by Google Translate.');
        }

        return array_combine(array_keys($texts), $translatedArray);
    }
}
