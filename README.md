# Laravel Auto Translation

[![Latest Stable Version](https://poser.pugx.org/vildanbina/laravel-auto-translation/v)](https://packagist.org/packages/vildanbina/laravel-auto-translation)
[![Total Downloads](https://poser.pugx.org/vildanbina/laravel-auto-translation/downloads)](https://packagist.org/packages/vildanbina/laravel-auto-translation)
[![License](https://poser.pugx.org/vildanbina/laravel-auto-translation/license)](https://packagist.org/packages/vildanbina/laravel-auto-translation)
[![PHP Version Require](https://poser.pugx.org/vildanbina/laravel-auto-translation/require/php)](https://packagist.org/packages/vildanbina/laravel-auto-translation)

## Introduction

**Laravel Auto Translation** is a robust package designed to simplify and streamline the localization of your Laravel application. By automating the translation of your language files, this package ensures a more efficient workflow. Key features include support for multiple drivers (ChatGPT, Google Translate, and DeepL) and compatibility with both JSON and PHP language files. Two main commands, `translate:scan` and `translate:default`, enable seamless scanning and translating of language strings.

## Requirements

- PHP >= 8.0
- Laravel 9.x, 10.x, or 11.x

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

The configuration file is located at `config/auto-translations.php`. Below is an example of its default settings. Customize these settings to suit your application, such as specifying the default driver or changing the source language.

~~~php
<?php

return [
    'lang_path' => lang_path(),

    'default_driver' => env('TRANSLATION_DEFAULT_DRIVER', 'chatgpt'),

    'source_language' => env('TRANSLATION_SOURCE_LANGUAGE', 'en'),

    'drivers' => [
        'chatgpt' => [
            'api_key'     => env('CHATGPT_API_KEY'),
            'model'       => env('CHATGPT_MODEL', 'gpt-3.5-turbo'),
            'temperature' => env('CHATGPT_TEMPERATURE', 0.7),
            'max_tokens'  => env('CHATGPT_MAX_TOKENS', 1000),
        ],

        'google' => [
            'api_key' => env('GOOGLE_API_KEY'),
        ],

        'deepl' => [
            'api_key' => env('DEEPL_API_KEY'),
            'api_url' => env('DEEPL_API_URL', 'https://api-free.deepl.com/v2/translate'),
        ],
    ],
];
~~~

### Setting Up Environment Variables

Add the required API keys to your `.env` file. Obtain these keys from the respective service providers:

- **ChatGPT**: Visit [OpenAI API documentation](https://platform.openai.com/docs/) to generate an API key.
- **Google Translate**: Obtain an API key from the [Google Cloud Console](https://console.cloud.google.com/).
- **DeepL**: Generate your API key from the [DeepL Pro Account](https://www.deepl.com/pro.html).

~~~env
TRANSLATION_DEFAULT_DRIVER=chatgpt
TRANSLATION_SOURCE_LANGUAGE=en
CHATGPT_API_KEY=your-chatgpt-api-key
GOOGLE_API_KEY=your-google-api-key
DEEPL_API_KEY=your-deepl-api-key
~~~

## Commands

### 1. `translate:scan`

This command scans all PHP files located within the `lang/` folder (including nested directories), extracting translatable strings and saving them in a JSON file (`lang/texts_to_translate.json`). This file serves as the base for subsequent translations.

#### Signature:

| Command           | Description                                                |
|-------------------|------------------------------------------------------------|
| `translate:scan`  | Extracts translatable strings from PHP files in `lang/` and saves them in `texts_to_translate.json`. |

**Options**:
- `--lang=`: (Optional) Source language code. Defaults to the value in the configuration file.

#### Usage:

~~~bash
php artisan translate:scan --lang=en
~~~

#### Example Output:
- Extracted strings are stored in `lang/texts_to_translate.json`.

### 2. `translate:default`

This command translates the strings defined in `texts_to_translate.json` into a specified target language using the chosen translation driver.

#### Signature:

| Command              | Description                                                                             |
|----------------------|-----------------------------------------------------------------------------------------|
| `translate:default`  | Translates strings from `texts_to_translate.json` into the specified target language.   |

**Arguments**:
- `target_lang`: Target language code.

**Options**:
- `--source_lang=`: (Optional) Source language code. Defaults to the value in the configuration file.
- `--driver=`: (Optional) Translation driver to use. Defaults to the value in the configuration file.
- `--overwrite`: (Optional) Overwrite existing translations if they already exist.

#### Usage:

~~~bash
php artisan translate:default fr --driver=deepl --overwrite
~~~

#### Example Output:
- Translations are stored in respective language files, e.g., `lang/fr.json`.

## Supported Drivers

The package supports:
- **ChatGPT**: Flexible and context-aware translations.
- **Google Translate API**: Fast and reliable.
- **DeepL API**: Known for accurate translations, especially for European languages.

## To Do

- Add support for creating custom translation drivers. For example, contributors could implement drivers that use alternative translation APIs, integrate with offline translation tools, or support specific regional dialects.
- Enhance error handling for failed API calls.
- Provide additional options for partial translations in complex projects.

## Contributing

Please see [CONTRIBUTING](.github/CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please e-mail vildanbina@gmail.com to report any security vulnerabilities instead of the issue tracker.

## Credits

- [Vildan Bina](https://github.com/vildanbina)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

