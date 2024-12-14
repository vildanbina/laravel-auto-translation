<?php

declare(strict_types=1);

namespace VildanBina\LaravelAutoTranslation\Drivers;

use Exception;
use Illuminate\Support\Facades\Http;
use VildanBina\LaravelAutoTranslation\Contracts\TranslationDriver;

final class DeepLDriver implements TranslationDriver
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

        // Concatenate all texts into a single string with newlines
        $joinedTexts = implode("\n", array_values($texts));

        // Prepare form parameters
        $formParams = [
            'auth_key'    => $apiKey,
            'text'        => $joinedTexts,
            'source_lang' => mb_strtoupper($sourceLang),
            'target_lang' => mb_strtoupper($targetLang),
        ];

        // Send POST request with form data
        $response = Http::asForm()->post($apiUrl, $formParams);

        if ( ! $response->successful()) {
            $error = $response->json()['message'] ?? $response->body();
            throw new Exception('DeepL API error: ' . $error);
        }

        // Retrieve and split the translated text back into an array
        $translatedText = $response->json()['translations'][0]['text'];
        $translatedArray = explode("\n", $translatedText);

        if (count($translatedArray) !== count($texts)) {
            throw new Exception('Mismatch in number of translated texts returned by DeepL.');
        }

        // Map translations back to the original keys
        $translatedTexts = array_combine(array_keys($texts), $translatedArray);

        return $translatedTexts;
    }
}
