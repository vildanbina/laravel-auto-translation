<?php

declare(strict_types=1);

namespace VildanBina\LaravelAutoTranslation\Services;

use Exception;
use VildanBina\LaravelAutoTranslation\Contracts\TranslationDriver;

final class TranslationService
{
    private $drivers = [];

    public function translate(array $texts, string $sourceLang, string $targetLang, string $driverName): array
    {
        $driver = $this->getDriver($driverName);

        return $driver->translate($texts, $sourceLang, $targetLang);
    }

    private function getDriver(string $driverName): TranslationDriver
    {
        if (isset($this->drivers[$driverName])) {
            return $this->drivers[$driverName];
        }

        $driverClass = $this->resolveDriverClass($driverName);

        if ( ! $driverClass) {
            throw new Exception("Driver [{$driverName}] not supported.");
        }

        $config = $this->getDriverConfig($driverName);

        return $this->drivers[$driverName] = new $driverClass($config);
    }

    private function resolveDriverClass(string $driverName): ?string
    {
        $drivers = [
            'chatgpt' => \VildanBina\LaravelAutoTranslation\Drivers\ChatGPTDriver::class,
            'google'  => \VildanBina\LaravelAutoTranslation\Drivers\GoogleTranslateDriver::class,
            'deepl'   => \VildanBina\LaravelAutoTranslation\Drivers\DeepLDriver::class,
        ];

        return $drivers[$driverName] ?? null;
    }

    private function getDriverConfig(string $driverName): array
    {
        return config("auto-translations.drivers.{$driverName}", []);
    }
}
