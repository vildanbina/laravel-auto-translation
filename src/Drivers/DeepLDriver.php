<?php

namespace VildanBina\LaravelAutoTranslation\Drivers;

use Exception;
use Illuminate\Support\Facades\Http;
use VildanBina\LaravelAutoTranslation\Contracts\TranslationDriver;

class DeepLDriver implements TranslationDriver
{
    private $config;

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    public function translate(array $texts, string $sourceLang, string $targetLang): array
    {
        $apiKey = $this->config['api_key'];
        $apiUrl = $this->config['api_url'];

        $joinedTexts = implode("\n", array_values($texts));
        $formParams = [
            'auth_key' => $apiKey,
            'text' => $joinedTexts,
            'source_lang' => mb_strtoupper($sourceLang),
            'target_lang' => mb_strtoupper($targetLang),
        ];

        $response = Http::asForm()->post($apiUrl, $formParams);
        if (!$response->successful()) {
            $error = $response->json()['message'] ?? $response->body();
            throw new Exception('DeepL API error: ' . $error);
        }

        $translatedArray = explode("\n", $response->json()['translations'][0]['text']);
        if (count($translatedArray) !== count($texts)) {
            throw new Exception('Mismatch in number of translated texts returned by DeepL.');
        }

        return array_combine(array_keys($texts), $translatedArray);
    }
}
