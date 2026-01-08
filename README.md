# Laravel Auto Translation

[![Latest Stable Version](https://poser.pugx.org/vildanbina/laravel-auto-translation/v)](https://packagist.org/packages/vildanbina/laravel-auto-translation)
[![Total Downloads](https://poser.pugx.org/vildanbina/laravel-auto-translation/downloads)](https://packagist.org/packages/vildanbina/laravel-auto-translation)
[![License](https://poser.pugx.org/vildanbina/laravel-auto-translation/license)](https://packagist.org/packages/vildanbina/laravel-auto-translation)
[![PHP Version Require](https://poser.pugx.org/vildanbina/laravel-auto-translation/require/php)](https://packagist.org/packages/vildanbina/laravel-auto-translation)

## Introduction

**Laravel Auto Translation** is a robust package designed to simplify and streamline the localization of your Laravel
application. By automating the translation of your language files, this package ensures a more efficient workflow. Key
features include:

1. **Multiple Drivers**: OpenAI, DeepSeek, Google Translate, and DeepL, and **Ollama (Local AI)**.
2. **JSON & PHP Language File Support**: Scans both JSON and nested PHP files.
3. **Placeholder Preservation**: Automatically protects placeholders like `:attribute` or `:seconds` from being altered.

## Requirements

- PHP >= 8.0
- Laravel 9.x, 10.x, 11.x or 12.x

## Installation

Install the package using Composer:

~~~bash
composer require vildanbina/laravel-auto-translation
~~~

Publish the configuration file:

~~~bash
php artisan vendor:publish --provider="VildanBina\LaravelAutoTranslation\AutoTranslationsServiceProvider"
~~~

## Configuration

The configuration file is located at `config/auto-translations.php`. Below is an example of its default settings.
Customize these settings to suit your application, such as specifying the default driver or changing the source
language:

~~~php
<?php

return [
    'lang_path' => lang_path(),

    'default_driver' => env('TRANSLATION_DEFAULT_DRIVER', 'openai'),

    'source_language' => env('TRANSLATION_SOURCE_LANGUAGE', 'en'),

    'drivers' => [
        'openai' => [
            'api_key'     => env('OPENAI_API_KEY'),
            'model'       => env('OPENAI_MODEL', 'gpt-3.5-turbo'),
            'temperature' => env('OPENAI_TEMPERATURE', 0.7),
            'max_tokens'  => env('OPENAI_MAX_TOKENS', 1000),
            'http_timeout' => env('OPENAI_HTTP_TIMEOUT', 30),
        ],
        'google' => [
            'api_key' => env('GOOGLE_API_KEY'),
        ],
        'deepl' => [
            'api_key' => env('DEEPL_API_KEY'),
            'api_url' => env('DEEPL_API_URL', 'https://api-free.deepl.com/v2/translate'),
        ],
        'deepseek' => [
            'api_key'      => env('DEEPSEEK_API_KEY'),
            'api_url'      => env('DEEPSEEK_API_URL', 'https://api.deepseek.com'),
            'model'        => env('DEEPSEEK_MODEL', 'deepseek-chat'),
            'temperature'  => env('DEEPSEEK_TEMPERATURE', 0),
            'max_tokens'   => env('DEEPSEEK_MAX_TOKENS', 2000),
            'http_timeout' => env('DEEPSEEK_HTTP_TIMEOUT', 60),
        ],
        'ollama' => [
            'api_url'      => env('OLLAMA_API_URL', 'http://localhost:11434/v1'),
            'model'        => env('OLLAMA_MODEL', 'llama3'),
            'max_tokens'   => env('OLLAMA_MAX_TOKENS', 2048),
            'temperature'  => 0,
            'http_timeout' => 60,
        ],

        // Example of a custom driver registration:
        // 'my_custom_driver' => [
        //     'class'   => \App\Drivers\MyCustomDriver::class,
        //     'api_key' => env('MY_CUSTOM_API_KEY'),
        // ],
    ],
];
~~~

### Setting Up Environment Variables

Add the required API keys to your `.env` file. Obtain these keys from the respective service providers:

- **OpenAI**: Visit [OpenAI API documentation](https://platform.openai.com/docs/) to generate an API key.
- **DeepSeek**: Obtain an API key from the [DeepSeek Platform](https://platform.deepseek.com/).
- **Google Translate**: Obtain an API key from the [Google Cloud Console](https://console.cloud.google.com/).
- **DeepL**: Generate your API key from the [DeepL Pro Account](https://www.deepl.com/pro.html).
- **Ollama (Local AI)**: No API key required. Install [Ollama](https://ollama.com/) and pull a model of your choice.
  
  **Commonly used models:**
  * `ollama pull llama3` (Meta)
  * `ollama pull deepseek-r1` (DeepSeek)
  * `ollama pull gemma3` (Google)
  * `ollama pull qwen2.5` (Alibaba)
  
  > [!TIP]
  > You can explore the full list of available models at the [Ollama Model Library](https://ollama.com/library).
  
> [!TIP]
> **Docker/Sail Users:** If running Laravel in a container, set `OLLAMA_API_URL=http://host.docker.internal:11434/v1` to allow the container to reach the Ollama service running on your host machine.

```env
# --- Global Settings ---
TRANSLATION_DEFAULT_DRIVER=ollama
TRANSLATION_SOURCE_LANGUAGE=en

# --- Ollama (Local AI) Settings ---
OLLAMA_MODEL=llama3
OLLAMA_API_URL=http://localhost:11434/v1

# --- OpenAI Settings ---
OPENAI_API_KEY=your-openai-api-key

# --- DeepSeek Settings ---
DEEPSEEK_API_KEY=your-deepseek-api-key

# --- Google & DeepL Settings ---
GOOGLE_API_KEY=your-google-api-key
DEEPL_API_KEY=your-deepl-api-key

```

## Commands

### 1. `translate:scan`

This command scans all PHP files located within the `lang/` folder (including nested directories), extracting
translatable strings and saving them in a JSON file (`lang/texts_to_translate.json`). This file serves as the base for
subsequent translations.

**Usage**:

~~~bash
php artisan translate:scan --lang=en
~~~

### 2. `translate:default`

This command translates the strings defined in `texts_to_translate.json` into a specified target language using the
chosen translation driver. It also preserves Laravel placeholders from being translated.

**Usage**:

~~~bash
php artisan translate:default fr --driver=deepl --overwrite
~~~

- `target_lang` (Argument) – The target language code (e.g., `fr`).
- `--source_lang` (Optional) – Source language code; defaults to config value.
- `--driver` (Optional) – Translation driver; defaults to config value.
- `--overwrite` (Optional) – Whether to overwrite existing translations.

### 3. Using In-Memory Texts (If Applicable)

In addition to scanning and translating language files, you can programmatically set texts directly in memory. This is useful for scenarios where you want to translate specific texts without relying on language files.

**Example:**

~~~php
use VildanBina\LaravelAutoTranslation\TranslationWorkflowService;
use VildanBina\LaravelAutoTranslation\Services\TranslationEngineService;

// Assume $translationEngineService is an instance of TranslationEngineService
$translationWorkflowService = new TranslationWorkflowService($translationEngineService);

// Define texts to translate
$texts = [
    'welcome.message' => 'Welcome to our application!',
    'user.greeting' => 'Hello, :name!',
];

// Set texts in memory
$translationWorkflowService->setInMemoryTexts($texts);

// Perform translation
$translatedTexts = $translationWorkflowService->translate('en', 'fr', 'deepl');

// Output translated texts
print_r($translatedTexts);
~~~

## Custom Drivers

To add a custom driver, follow these steps:

1. **Implement the `TranslationDriver` interface**:
   ~~~php
   use VildanBina\LaravelAutoTranslation\Contracts\TranslationDriver;

   class MyCustomDriver implements TranslationDriver
   {
       public function __construct(private array $config)
       {
       }

       public function translate(array $texts, string $sourceLang, string $targetLang): array
       {
           // Your custom logic...
           return $texts;
       }
   }
   ~~~

2. **Register the driver in `auto-translations.php`**:
   ~~~php
   'drivers' => [
       'my_custom_driver' => [
           'class'   => \App\Drivers\MyCustomDriver::class,
           'api_key' => env('MY_CUSTOM_API_KEY'),
           // additional config...
       ],
   ],
   ~~~

3. **Use it in translations**:
   ~~~bash
   php artisan translate:default fr --driver=my_custom_driver
   ~~~

## Supported Drivers

- **OpenAI**: Flexible and context-aware translations.
- **DeepSeek**: High-performance and cost-effective OpenAI-compatible API.
- **Ollama**: **Free and Private.** Run models like Llama 3 or DeepSeek-R1 locally on your own hardware.
- **Google Translate**: Fast and reliable.
- **DeepL**: Known for accurate translations, especially for European languages.
- **Custom Driver**: Extendable for your own APIs or offline services.

## Contributing

See [CONTRIBUTING](.github/CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please e-mail vildanbina@gmail.com to report any security vulnerabilities instead of using the issue tracker.

## Credits

- [Vildan Bina](https://github.com/vildanbina)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
