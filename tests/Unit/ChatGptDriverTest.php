<?php

namespace VildanBina\LaravelAutoTranslation\Tests\Unit;

use Exception;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;
use VildanBina\LaravelAutoTranslation\Drivers\ChatGPTDriver;

class ChatGptDriverTest extends TestCase
{
    public function test_chatgpt_driver_translates_texts(): void
    {
        Http::fake([
            'https://api.openai.com/*' => Http::response([
                'choices' => [
                    [
                        'message' => [
                            'content' => "Hola\nAdiós",
                        ],
                    ],
                ],
            ]),
        ]);

        $driver = new ChatGPTDriver(['api_key' => 'test-key']);

        $result = $driver->translate(['hello' => 'Hello', 'bye' => 'Goodbye'], 'en', 'es');

        $this->assertEquals(['hello' => 'Hola', 'bye' => 'Adiós'], $result);
    }

    public function test_chatgpt_driver_handles_api_errors(): void
    {
        Http::fake([
            'https://api.openai.com/*' => Http::response(['error' => ['message' => 'Invalid API key']], 400),
        ]);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('ChatGPT API error: Invalid API key');

        $driver = new ChatGPTDriver(['api_key' => 'invalid-key']);
        $driver->translate(['hello' => 'Hello'], 'en', 'es');
    }
}
