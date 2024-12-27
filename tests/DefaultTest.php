<?php

namespace VildanBina\LaravelAutoTranslation\Tests;

use Tests\TestCase;
use VildanBina\LaravelAutoTranslation\AutoTranslationsServiceProvider;

class DefaultTest extends TestCase
{
    public function test_service_provider_loaded(): void
    {
        $this->assertTrue(app()->providerIsLoaded(AutoTranslationsServiceProvider::class));
    }

    public function test_config_published(): void
    {
        $config = config('auto-translations');
        $this->assertNotNull($config, 'Configuration file is not published.');
        $this->assertArrayHasKey('default_driver', $config);
    }

    protected function getPackageProviders($app)
    {
        return [AutoTranslationsServiceProvider::class];
    }
}
