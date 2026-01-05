<?php

namespace VildanBina\LaravelAutoTranslation\Tests\Unit;

use Exception;
use Illuminate\Support\Facades\Http;
use VildanBina\LaravelAutoTranslation\Tests\TestCase;
use VildanBina\LaravelAutoTranslation\Drivers\OpenAIDriver;

class OpenAIDriverTest extends TestCase
{
    public function test_openai_driver_translates_texts(): void
    {
        Http::fake([
            'https://api.openai.com/*' => Http::response([
                'choices' => [
                    [
                        'message' => [
                            'content' => json_encode(['hello' => 'Hola', 'bye' => 'Adiós']),
                        ],
                    ],
                ],
            ]),
        ]);

        $driver = new OpenAIDriver(['api_key' => 'test-key']);

        $result = $driver->translate(['hello' => 'Hello', 'bye' => 'Goodbye'], 'en', 'es');

        $this->assertEquals(['hello' => 'Hola', 'bye' => 'Adiós'], $result);
    }

    public function test_openai_driver_handles_api_errors(): void
    {
        Http::fake([
            'https://api.openai.com/*' => Http::response(['error' => ['message' => 'Invalid API key']], 400),
        ]);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('OpenAI API error: Invalid API key');

        $driver = new OpenAIDriver(['api_key' => 'invalid-key']);
        $driver->translate(['hello' => 'Hello'], 'en', 'es');
    }
}