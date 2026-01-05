<?php

namespace VildanBina\LaravelAutoTranslation\Tests;

use Orchestra\Testbench\TestCase as Orchestra;
use VildanBina\LaravelAutoTranslation\AutoTranslationsServiceProvider;

class TestCase extends Orchestra
{
    /**
     * Load the package service provider.
     */
    protected function getPackageProviders($app)
    {
        return [
            AutoTranslationsServiceProvider::class,
        ];
    }

    /**
     * Define environment setup.
     */
    protected function getEnvironmentSetUp($app): void
    {
        // Setup default config for tests 
        $app['config']->set('auto-translations.default_driver', 'openai');
    }
}