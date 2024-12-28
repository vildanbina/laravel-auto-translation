<?php

namespace VildanBina\LaravelAutoTranslation\Commands;

use Exception;
use Illuminate\Console\Command;
use VildanBina\LaravelAutoTranslation\TranslationWorkflowService;

class ScanTextCommand extends Command
{
    protected $signature = 'translate:scan
                            {--lang= : Source language code (defaults to config value)}';
    protected $description = 'Scan language files in lang_path and prepare strings for translation';

    public function handle(TranslationWorkflowService $manager): void
    {
        try {
            $sourceLang = $this->option('lang') ?: config('auto-translations.source_language');
            $manager->scanLanguageFiles($sourceLang);
            $this->info("Language files in '{$sourceLang}' have been scanned and prepared for translation.");
        } catch (Exception $e) {
            $this->error('An error occurred during scanning: ' . $e->getMessage());
        }
    }
}
