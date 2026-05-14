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

        $body = [
            'q' => array_values($texts),
            'source' => $sourceLang,
            'target' => $targetLang,
            'format' => 'text',
        ];

        $response = Http::asJson()->post(
            'https://translation.googleapis.com/language/translate/v2?key=' . urlencode($apiKey),
            $body
        );

        if (!$response->successful()) {
            $error = $response->json()['error']['message'] ?? $response->body();
            throw new Exception('Google Translate API error: ' . $error);
        }

        $translations = collect($response->json('data.translations'))
            ->pluck('translatedText')
            ->toArray();

        if (count($translations) !== count($texts)) {
            throw new Exception('Mismatch in number of translated texts returned by Google Translate.');
        }

        return array_combine(array_keys($texts), $translations);
    }
}
