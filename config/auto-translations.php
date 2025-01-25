<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Language Files Path
    |--------------------------------------------------------------------------
    |
    | The base path where your language files are stored. By default, it's
    | the 'lang' directory. You can change this to match your application's
    | structure.
    |
    */

    'lang_path' => lang_path(),

    /*
    |--------------------------------------------------------------------------
    | Default Translation Driver
    |--------------------------------------------------------------------------
    |
    | The default translation driver to use when none is specified. You can
    | set this to any of the drivers defined in the 'drivers' array below.
    |
    */

    'default_driver' => env('TRANSLATION_DEFAULT_DRIVER', 'chatgpt'),

    /*
    |--------------------------------------------------------------------------
    | Source Language Code
    |--------------------------------------------------------------------------
    |
    | The default source language code of your application. This will be used
    | as the source language for translations unless specified otherwise.
    |
    */

    'source_language' => env('TRANSLATION_SOURCE_LANGUAGE', 'en'),

    /*
    |--------------------------------------------------------------------------
    | Available Translation Drivers
    |--------------------------------------------------------------------------
    |
    | Configure as many translation drivers as you wish. Each driver should
    | have a unique name and its own configuration settings.
    |
    */

    'drivers' => [

        'chatgpt' => [
            'api_key' => env('CHATGPT_API_KEY'),
            'model' => env('CHATGPT_MODEL', 'gpt-3.5-turbo'),
            'temperature' => (float) env('CHATGPT_TEMPERATURE', 0.7),
            'max_tokens' => (int) env('CHATGPT_MAX_TOKENS', 4096),
            'http_timeout' => (int) env('CHATGPT_HTTP_TIMEOUT', 300),
        ],

        'google' => [
            'api_key' => env('GOOGLE_API_KEY'),
        ],

        'deepl' => [
            'api_key' => env('DEEPL_API_KEY'),
            'api_url' => env('DEEPL_API_URL', 'https://api-free.deepl.com/v2/translate'),
        ],
        'my_custom_driver' => [
            'class' => App\Drivers\MyCustomDriver::class,
            'api_key' => env('MY_CUSTOM_API_KEY'),
            // ...
        ],
    ],
];
