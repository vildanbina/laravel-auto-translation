<?php

declare(strict_types=1);

namespace VildanBina\LaravelAutoTranslation\Drivers;

use Exception;
use Illuminate\Support\Facades\Http;
use VildanBina\LaravelAutoTranslation\Contracts\TranslationDriver;

final class GoogleTranslateDriver implements TranslationDriver
{
    private $config;

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    public function translate(array $texts, string $sourceLang, string $targetLang): array
    {
        $apiKey = $this->config['api_key'];

        // Concatenate all texts into a single string with newlines
        $joinedTexts = implode("\n", array_values($texts));

        // Prepare parameters
        $params = [
            'q'      => $joinedTexts,
            'source' => $sourceLang,
            'target' => $targetLang,
            'key'    => $apiKey,
            'format' => 'text',
        ];

        // Send POST request to the Google Translate API
        $response = Http::asForm()->post('https://translation.googleapis.com/language/translate/v2', $params);

        if ( ! $response->successful()) {
            $error = $response->json()['error']['message'] ?? $response->body();
            throw new Exception('Google Translate API error: ' . $error);
        }

        // Retrieve the translated text
        $translatedText = $response->json()['data']['translations'][0]['translatedText'];

        // Split the translated text back into an array
        $translatedArray = explode("\n", $translatedText);

        if (count($translatedArray) !== count($texts)) {
            throw new Exception('Mismatch in number of translated texts returned by Google Translate.');
        }

        // Map translations back to the original keys
        $translatedTexts = array_combine(array_keys($texts), $translatedArray);

        return $translatedTexts;
    }
}
