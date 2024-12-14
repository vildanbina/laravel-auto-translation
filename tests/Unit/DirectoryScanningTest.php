<?php

declare(strict_types=1);

namespace VildanBina\LaravelAutoTranslation\Tests\Unit;

use Exception;
use Mockery;
use Tests\TestCase;
use VildanBina\LaravelAutoTranslation\TranslationsManager;

final class DirectoryScanningTest extends TestCase
{
    public function test_scan_text_command_executes_successfully(): void
    {
        $manager = Mockery::mock(TranslationsManager::class);
        $manager->shouldReceive('scanLanguageFiles')
            ->once()
            ->with('en')
            ->andReturnTrue();

        $this->app->instance(TranslationsManager::class, $manager);

        $this->artisan('translate:scan', ['--lang' => 'en'])
            ->expectsOutput("Language files in 'en' have been scanned and prepared for translation.")
            ->assertExitCode(0);
    }

    public function test_scan_text_command_handles_exceptions(): void
    {
        $manager = Mockery::mock(TranslationsManager::class);
        $manager->shouldReceive('scanLanguageFiles')
            ->andThrow(new Exception('Test exception'));

        $this->app->instance(TranslationsManager::class, $manager);

        $this->artisan('translate:scan')
            ->expectsOutput('An error occurred during scanning: Test exception')
            ->assertExitCode(0);
    }
}
