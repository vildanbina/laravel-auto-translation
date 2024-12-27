<?php

namespace VildanBina\LaravelAutoTranslation\Contracts;

interface TranslationDriver
{
    /**
     * Translate an array of texts to the specified language.
     *
     * @param  array  $texts  Array of texts to translate.
     * @param  string  $sourceLang  Source language code.
     * @param  string  $targetLang  Target language code.
     * @return array Translated texts.
     */
    public function translate(array $texts, string $sourceLang, string $targetLang): array;
}
