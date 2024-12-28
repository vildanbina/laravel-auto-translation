<?php

namespace VildanBina\LaravelAutoTranslation\Services;

use Exception;
use VildanBina\LaravelAutoTranslation\Contracts\TranslationDriver;
use VildanBina\LaravelAutoTranslation\Drivers\ChatGPTDriver;
use VildanBina\LaravelAutoTranslation\Drivers\DeepLDriver;
use VildanBina\LaravelAutoTranslation\Drivers\GoogleTranslateDriver;

class TranslationEngineService
{
    private $drivers = [];

    public function translate(array $texts, string $sourceLang, string $targetLang, string $driverName): array
    {
        [$maskedTexts, $placeholderMap] = $this->maskPlaceholders($texts);

        $driver = $this->getDriver($driverName);
        $translated = $driver->translate($maskedTexts, $sourceLang, $targetLang);

        return $this->restorePlaceholders($translated, $placeholderMap);
    }

    private function getDriver(string $driverName): TranslationDriver
    {
        if (isset($this->drivers[$driverName])) {
            return $this->drivers[$driverName];
        }

        $driverClass = $this->resolveDriverClass($driverName);
        if (!$driverClass) {
            throw new Exception("Driver [{$driverName}] not supported.");
        }

        $config = config("auto-translations.drivers.{$driverName}", []);

        return $this->drivers[$driverName] = new $driverClass($config);
    }

    private function resolveDriverClass(string $driverName): ?string
    {
        $drivers = [
            'chatgpt' => ChatGPTDriver::class,
            'google' => GoogleTranslateDriver::class,
            'deepl' => DeepLDriver::class,
        ];

        // If user has defined a "class" inside config for a custom driver, pick it up
        $customDriver = config("auto-translations.drivers.{$driverName}.class");

        return $customDriver ?? $drivers[$driverName] ?? null;
    }

    /**
     * Mask placeholders (e.g. :attribute, :seconds) with tokens to avoid them
     * being translated. Returns [maskedTexts, placeholderMap].
     */
    private function maskPlaceholders(array $texts): array
    {
        $maskedTexts = [];
        $placeholderMap = [];

        foreach ($texts as $key => $text) {
            $index = 0;
            // Updated pattern to include underscores, hyphens, digits, etc.
            $maskedTexts[$key] = preg_replace_callback('/(:[A-Za-z0-9_\-]+)/', function ($match) use (&$index, $key, &$placeholderMap) {
                $token = "%%PLACEHOLDER_{$key}_{$index}%%";
                $placeholderMap[$token] = $match[0];
                $index++;

                return $token;
            }, $text);
        }

        return [$maskedTexts, $placeholderMap];
    }

    /**
     * Restore all masked placeholders in the translated texts.
     */
    private function restorePlaceholders(array $translated, array $placeholderMap): array
    {
        foreach ($translated as $key => $text) {
            foreach ($placeholderMap as $token => $original) {
                if (str_contains($text, $token)) {
                    $text = str_replace($token, $original, $text);
                }
            }
            $translated[$key] = $text;
        }

        return $translated;
    }
}
