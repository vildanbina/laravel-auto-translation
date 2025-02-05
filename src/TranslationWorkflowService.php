<?php

namespace VildanBina\LaravelAutoTranslation;

use Exception;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\File;
use VildanBina\LaravelAutoTranslation\Services\TranslationEngineService;

class TranslationWorkflowService
{
    protected array $inMemoryTexts;

    protected TranslationEngineService $service;

    public function __construct(TranslationEngineService $service)
    {
        $this->service = $service;
    }

    public function setInMemoryTexts(array $texts): static
    {
        $this->inMemoryTexts = $texts;

        return $this;
    }

    public function scanLanguageFiles(string $lang): void
    {
        $texts = $this->loadLanguageFiles($lang);
        $this->storeTextsForTranslation($texts);
    }

    /**
     * Translates the loaded texts from source language to target language.
     *
     * @param  string  $sourceLang  The source language code.
     * @param  string  $targetLang  The target language code.
     * @param  string  $driver  The translation driver to use.
     * @param  bool  $overwrite  Whether to overwrite existing translations.
     * @return array Returns translated texts.
     *
     * @throws Exception If translation files are missing or invalid.
     */
    public function translate(string $sourceLang, string $targetLang, string $driver, bool $overwrite = false): array
    {
        $texts = $this->loadTexts();
        if (! $overwrite) {
            $texts = $this->compareTranslations($texts, $targetLang);
        }
        if (empty($texts)) {
            return [0,[]];
        }

        [$translated, $warnings] = $this->service->translate($texts, $sourceLang, $targetLang, $driver);

        if (isset($this->inMemoryTexts)) {
            return $translated;
        }

        $this->saveTranslated($translated, $targetLang, $overwrite);

        return [count($translated), $warnings];
    }

    /**
     * Stores the gathered texts to a JSON file for translation.
     *
     * @param  array  $texts  The array of texts to store.
     */
    private function storeTextsForTranslation(array $texts): void
    {
        $langPath = config('auto-translations.lang_path');
        $filePath = "{$langPath}/texts_to_translate.json";
        ksort($texts);
        File::put($filePath, json_encode($texts, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }

    /**
     * Loads texts either from in-memory storage or from the translation file.
     *
     * @return array The array of texts to be translated.
     *
     * @throws Exception If the translation file does not exist or has invalid format.
     */
    private function loadTexts(): array
    {
        if (isset($this->inMemoryTexts)) {
            return $this->inMemoryTexts;
        }

        $file = config('auto-translations.lang_path').'/texts_to_translate.json';
        if (! File::exists($file)) {
            throw new Exception("No texts found. Run 'translate:scan' first.");
        }

        $contents = json_decode(File::get($file), true);
        if (! is_array($contents)) {
            throw new Exception("Invalid format in 'texts_to_translate.json'.");
        }

        return $contents;
    }

    /**
     * Loads all language strings from JSON and PHP language files.
     *
     * @param  string  $lang  The language code to load.
     * @return array The array of loaded language strings.
     */
    private function loadLanguageFiles(string $lang): array
    {
        $dir = config('auto-translations.lang_path');
        $texts = [];

        $jsonFile = "{$dir}/{$lang}.json";
        if (File::exists($jsonFile)) {
            $jsonData = json_decode(File::get($jsonFile), true);
            if (is_array($jsonData)) {
                $texts = array_merge($texts, $jsonData);
            }
        }

        $langDir = "{$dir}/{$lang}";
        if (File::isDirectory($langDir)) {
            foreach (File::allFiles($langDir) as $file) {
                if ($file->getExtension() === 'php') {
                    $subKey = str_replace(['/', '.php'], ['.', ''], $file->getRelativePathname());
                    $array = include $file->getPathname();
                    if (is_array($array)) {
                        $flattened = Arr::dot([$subKey => $array]);
                        $texts = array_merge($texts, $flattened);

                    }
                }
            }
        }

        return $texts;
    }

    /**
     * Saves the translated texts to the target language file.
     *
     * @param  array  $translated  The array of translated texts.
     * @param  string  $lang  The target language code.
     * @param  bool  $overwrite  Whether to overwrite existing translations.
     */
    private function saveTranslated(array $translated, string $lang, bool $overwrite): void
    {
        $file = config('auto-translations.lang_path')."/{$lang}.json";
        $existing = [];

        if (File::exists($file)) {
            $existing = json_decode(File::get($file), true) ?: [];
        }

        if (! $overwrite) {
            $translated = array_merge($existing, $translated);
        }

        File::put($file, json_encode($translated, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }

    /**
     * Compare previously translated file and remove from texts to be translated.
     *
     * @param  array  $texts  The array of texts to be translated.
     * @param  string  $lang  The target language code.
     *
     * @return array The array of texts to be translated.
     */
    private function compareTranslations(array $texts, string $lang): array
    {
        $file = config('auto-translations.lang_path')."/{$lang}.json";

        if (!File::exists($file)) {
            return $texts;
        }

        $existing = json_decode(File::get($file), true) ?: [];
        foreach (array_keys($existing) as $key) {
            unset($texts[$key]);
        }

        return $texts;
    }
}
