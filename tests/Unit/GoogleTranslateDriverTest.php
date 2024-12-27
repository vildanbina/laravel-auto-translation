<?php

namespace VildanBina\LaravelAutoTranslation\Tests\Unit;

use Exception;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;
use VildanBina\LaravelAutoTranslation\Drivers\GoogleTranslateDriver;

class GoogleTranslateDriverTest extends TestCase
{
    public function test_google_driver_translates_texts(): void
    {
        Http::fake([
            'https://translation.googleapis.com/language/translate/v2' => Http::response([
                'data' => [
                    'translations' => [
                        ['translatedText' => "Hola\nAdiós"],
                    ],
                ],
            ]),
        ]);

        $driver = new GoogleTranslateDriver([
            'api_key' => 'test-google-api',
        ]);

        $result = $driver->translate(['hello' => 'Hello', 'bye' => 'Goodbye'], 'en', 'es');
        $this->assertEquals(['hello' => 'Hola', 'bye' => 'Adiós'], $result);
    }

    public function test_google_driver_handles_api_errors(): void
    {
        Http::fake([
            'https://translation.googleapis.com/language/translate/v2' => Http::response([
                'error' => [
                    'message' => 'Invalid API key',
                ],
            ], 400),
        ]);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Google Translate API error: Invalid API key');

        $driver = new GoogleTranslateDriver([
            'api_key' => 'invalid-key',
        ]);

        $driver->translate(['hello' => 'Hello'], 'en', 'es');
    }
}
