<?php

namespace VildanBina\LaravelAutoTranslation\Tests\Unit;

use Illuminate\Support\Facades\Http;
use VildanBina\LaravelAutoTranslation\Drivers\DeepSeekDriver;
use VildanBina\LaravelAutoTranslation\Tests\TestCase;

class DeepSeekDriverTest extends TestCase
{
    public function test_deepseek_driver_translates_texts(): void
    {
        Http::fake([
            'https://api.deepseek.com/*' => Http::response([
                'choices' => [
                    [
                        'message' => [
                            // DeepSeek returns a JSON string that the driver parses
                            'content' => json_encode(['hello' => 'Hola', 'bye' => 'Adiós']),
                        ],
                    ],
                ],
            ], 200),
        ]);

        $driver = new DeepSeekDriver([
            'api_key' => 'sk-test-key',
        ]);

        $result = $driver->translate(['hello' => 'Hello', 'bye' => 'Goodbye'], 'en', 'es');

        $this->assertEquals(['hello' => 'Hola', 'bye' => 'Adiós'], $result);
        
        Http::assertSent(function ($request) {
            return $request->hasHeader('Authorization', 'Bearer sk-test-key') &&
                   $request->url() === 'https://api.deepseek.com/chat/completions';
        });
    }

    public function test_deepseek_driver_handles_api_errors(): void
    {
        Http::fake([
            'https://api.deepseek.com/*' => Http::response([
                'error' => ['message' => 'Invalid API Key']
            ], 401),
        ]);

        $this->expectException(\Exception::class);

        $driver = new DeepSeekDriver(['api_key' => 'invalid-key']);
        $driver->translate(['hello' => 'Hello'], 'en', 'es');
    }
}