<?php

namespace VildanBina\LaravelAutoTranslation\Commands;

use Exception;
use Illuminate\Console\Command;
use VildanBina\LaravelAutoTranslation\TranslationWorkflowService;

class TranslateCommand extends Command
{
    protected $signature = 'translate:default
                            {target_lang : Target language code}
                            {--source_lang= : Source language code (defaults to config value)}
                            {--driver= : Translation driver to use (defaults to config value)}
                            {--overwrite : Overwrite existing translations}';
    protected $description = 'Translate language strings to another language using a specified driver.';

    public function handle(TranslationWorkflowService $manager): void
    {
        $targetLang = $this->argument('target_lang');
        $sourceLang = $this->option('source_lang') ?: config('auto-translations.source_language');
        $driver = $this->option('driver') ?: config('auto-translations.default_driver');
        $overwrite = $this->option('overwrite');

        try {
            $manager->translate($sourceLang, $targetLang, $driver, $overwrite);
            $this->info("Translation to '{$targetLang}' using '{$driver}' driver completed and saved.");
        } catch (Exception $e) {
            $this->error('An error occurred during translation: ' . $e->getMessage());
        }
    }
}
