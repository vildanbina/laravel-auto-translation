<?php

declare(strict_types=1);

namespace VildanBina\LaravelAutoTranslation;

use Exception;
use Illuminate\Support\Facades\File;
use VildanBina\LaravelAutoTranslation\Services\TranslationService;

final class TranslationsManager
{
    private $service;

    public function __construct(TranslationService $service)
    {
        $this->service = $service;
    }

    public function scanLanguageFiles(string $lang): void
    {
        $texts = $this->loadLanguageStrings($lang);

        // Store the texts in a format suitable for translation
        $this->storeTextsForTranslation($texts);
    }

    public function translate(string $sourceLang, string $targetLang, string $driver, bool $overwrite = false): void
    {
        $texts = $this->loadTextsForTranslation();

        $translatedTexts = $this->service->translate($texts, $sourceLang, $targetLang, $driver);

        $this->storeTranslations($translatedTexts, $targetLang, $overwrite);
    }

    private function storeTextsForTranslation(array $texts): void
    {
        $langPath = config('auto-translations.lang_path');
        $filePath = "{$langPath}/texts_to_translate.json";

        // Optionally, sort the texts alphabetically
        ksort($texts);

        File::put($filePath, json_encode($texts, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }

    private function loadTextsForTranslation(): array
    {
        $langPath = config('auto-translations.lang_path');
        $filePath = "{$langPath}/texts_to_translate.json";

        if ( ! File::exists($filePath)) {
            throw new Exception("Texts to translate not found. Please run 'translate:scan' first.");
        }

        $texts = json_decode(File::get($filePath), true);

        if ( ! is_array($texts)) {
            throw new Exception("Invalid format in 'texts_to_translate.json'.");
        }

        return $texts;
    }

    private function loadLanguageStrings(string $lang): array
    {
        $langPath = config('auto-translations.lang_path');
        $texts = [];

        // Load JSON language file if exists
        $jsonFile = "{$langPath}/{$lang}.json";
        if (File::exists($jsonFile)) {
            $jsonStrings = json_decode(File::get($jsonFile), true);
            if (is_array($jsonStrings)) {
                $texts = array_merge($texts, $jsonStrings);
            }
        }

        // Load PHP language files in subdirectories
        $langDir = "{$langPath}/{$lang}";
        if (File::isDirectory($langDir)) {
            $files = File::allFiles($langDir);
            foreach ($files as $file) {
                if ('php' === $file->getExtension()) {
                    $relativePath = $file->getRelativePathname();
                    $key = str_replace(['/', '.php'], ['.', ''], $relativePath);
                    $array = include $file->getPathname();
                    if (is_array($array)) {
                        $this->flattenArray($array, $key, $texts);
                    }
                }
            }
        }

        return $texts;
    }

    private function flattenArray(array $array, string $parentKey, array &$result): void
    {
        foreach ($array as $key => $value) {
            $fullKey = $parentKey . '.' . $key;
            if (is_array($value)) {
                $this->flattenArray($value, $fullKey, $result);
            } else {
                $result[$fullKey] = $value;
            }
        }
    }

    private function storeTranslations(array $translatedTexts, string $lang, bool $overwrite): void
    {
        $langPath = config('auto-translations.lang_path');
        $filePath = "{$langPath}/{$lang}.json";

        $existingTranslations = [];

        if (File::exists($filePath)) {
            $existingTranslations = json_decode(File::get($filePath), true) ?? [];
        }

        if ( ! $overwrite) {
            // Merge existing translations with new ones, preferring existing ones.
            $translatedTexts = array_merge($existingTranslations, $translatedTexts);
        }

        File::put($filePath, json_encode($translatedTexts, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }
}
