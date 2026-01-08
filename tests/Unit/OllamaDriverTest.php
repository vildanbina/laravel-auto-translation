<?php

namespace VildanBina\LaravelAutoTranslation\Tests\Unit;

use Exception;
use Illuminate\Support\Facades\Http;
use VildanBina\LaravelAutoTranslation\Drivers\OllamaDriver;
use VildanBina\LaravelAutoTranslation\Tests\TestCase;

class OllamaDriverTest extends TestCase
{
    private array $config;
    private OllamaDriver $driver;

    protected function setUp(): void
    {
        parent::setUp();

        $this->config = [
            'api_url' => 'http://localhost:11434/v1',
            'model' => 'llama3',
            'max_tokens' => 1000,
            'temperature' => 0,
            'http_timeout' => 30,
        ];

        $this->driver = new OllamaDriver($this->config);
    }

    /** @test */
    public function it_can_successfully_translate_texts()
    {
        $texts = [
            'welcome' => 'Welcome to our application',
            'login' => 'Please log in'
        ];

        $mockResponse = [
            'choices' => [
                [
                    'message' => [
                        'content' => json_encode([
                            'welcome' => 'Bienvenue dans notre application',
                            'login' => 'Veuillez vous connecter'
                        ])
                    ]
                ]
            ]
        ];

        Http::fake([
            'http://localhost:11434/v1/chat/completions' => Http::response($mockResponse, 200)
        ]);

        $result = $this->driver->translate($texts, 'en', 'fr');

        $this->assertEquals('Bienvenue dans notre application', $result['welcome']);
        $this->assertEquals('Veuillez vous connecter', $result['login']);
        $this->assertCount(2, $result);
    }

    /** @test */
    public function it_throws_exception_on_api_error()
    {
        Http::fake([
            'http://localhost:11434/v1/chat/completions' => Http::response(['error' => ['message' => 'Model not found']], 500)
        ]);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Ollama API error: Model not found');

        $this->driver->translate(['hello' => 'Hello'], 'en', 'es');
    }

    /** @test */
    public function it_throws_exception_on_invalid_json_response()
    {
        Http::fake([
            'http://localhost:11434/v1/chat/completions' => Http::response([
                'choices' => [['message' => ['content' => 'Not a JSON string']]]
            ], 200)
        ]);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Invalid JSON returned by Ollama.');

        $this->driver->translate(['hello' => 'Hello'], 'en', 'es');
    }

    /** @test */
    public function it_handles_chunking_when_exceeding_token_limit()
    {
        // Setup a small max_tokens to force chunking
        $config = array_merge($this->config, ['max_tokens' => 50]);
        $driver = new OllamaDriver($config);

        $texts = [
            'key1' => 'This is a long sentence that will likely trigger chunking logic.',
            'key2' => 'Another long sentence to ensure we get at least two requests.'
        ];

        Http::fake([
            'http://localhost:11434/v1/chat/completions' => Http::sequence()
                ->push(['choices' => [['message' => ['content' => json_encode(['key1' => 'Trans 1'])]]]])
                ->push(['choices' => [['message' => ['content' => json_encode(['key2' => 'Trans 2'])]]]])
        ]);

        $result = $driver->translate($texts, 'en', 'de');

        $this->assertEquals('Trans 1', $result['key1']);
        $this->assertEquals('Trans 2', $result['key2']);
        
        // Assert that two distinct HTTP requests were made due to chunking
        Http::assertSentCount(2);
    }

    /** @test */
    public function it_throws_exception_on_mismatched_response_count()
    {
        Http::fake([
            'http://localhost:11434/v1/chat/completions' => Http::response([
                'choices' => [
                    [
                        'message' => [
                            'content' => json_encode(['only_one_key' => 'Value'])
                        ]
                    ]
                ]
            ], 200)
        ]);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Mismatch in translation count from Ollama.');

        $this->driver->translate([
            'key1' => 'Value 1',
            'key2' => 'Value 2'
        ], 'en', 'es');
    }
}