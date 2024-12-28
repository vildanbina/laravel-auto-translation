<?php

namespace VildanBina\LaravelAutoTranslation\Tests\Unit;

use Exception;
use Mockery;
use Tests\TestCase;
use VildanBina\LaravelAutoTranslation\TranslationWorkflowService;

class TranslateCommandTest extends TestCase
{
    public function test_translate_command_executes_successfully(): void
    {
        $manager = Mockery::mock(TranslationWorkflowService::class);
        $manager->shouldReceive('translate')
            ->once()
            ->with('en', 'es', 'google', false)
            ->andReturnTrue();

        $this->app->instance(TranslationWorkflowService::class, $manager);

        $this->artisan('translate:default', [
            'target_lang' => 'es',
            '--source_lang' => 'en',
            '--driver' => 'google',
        ])->expectsOutput("Translation to 'es' using 'google' driver completed and saved.")
            ->assertExitCode(0);
    }

    public function test_translate_command_handles_exceptions(): void
    {
        $manager = Mockery::mock(TranslationWorkflowService::class);
        $manager->shouldReceive('translate')
            ->andThrow(new Exception('Test exception'));

        $this->app->instance(TranslationWorkflowService::class, $manager);

        $this->artisan('translate:default', ['target_lang' => 'es'])
            ->expectsOutput('An error occurred during translation: Test exception')
            ->assertExitCode(0);
    }
}
