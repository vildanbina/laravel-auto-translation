<?php

namespace VildanBina\LaravelAutoTranslation\Tests\Unit;

use Illuminate\Support\Facades\Http;
use Tests\TestCase;
use VildanBina\LaravelAutoTranslation\Drivers\DeepLDriver;

class DeeplDriverTest extends TestCase
{
    public function test_deepl_driver_translates_texts(): void
    {
        Http::fake([
            'https://api-free.deepl.com/*' => Http::response([
                'translations' => [
                    ['text' => "Hola\nAdiós"],
                ],
            ]),
        ]);

        $driver = new DeepLDriver([
            'api_key' => 'test-key',
            'api_url' => 'https://api-free.deepl.com/v2/translate',
        ]);

        $result = $driver->translate(['hello' => 'Hello', 'bye' => 'Goodbye'], 'en', 'es');
        $this->assertEquals(['hello' => 'Hola', 'bye' => 'Adiós'], $result);
    }
}
