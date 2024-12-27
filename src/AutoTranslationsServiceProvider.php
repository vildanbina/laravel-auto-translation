<?php

namespace VildanBina\LaravelAutoTranslation;

use Illuminate\Support\ServiceProvider;
use VildanBina\LaravelAutoTranslation\Commands\ScanTextCommand;
use VildanBina\LaravelAutoTranslation\Commands\TranslateCommand;

class AutoTranslationsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/auto-translations.php', 'auto-translations');
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__ . '/../config/auto-translations.php' => config_path('auto-translations.php'),
        ]);

        if ($this->app->runningInConsole()) {
            $this->commands([
                TranslateCommand::class,
                ScanTextCommand::class,
            ]);
        }
    }
}
