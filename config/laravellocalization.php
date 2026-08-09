<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Supported Locales
    |--------------------------------------------------------------------------
    |
    | Define the locales that your application supports. Each locale key is the
    | short code used in URLs (fa, en). The values are arrays used for
    | convenience in generators and selectors.
    |
    */

    'supportedLocales' => [
        'fa' => [
            'name' => 'فارسی',
            'script' => 'Arabic',
            'native' => 'فارسی',
            'regional' => 'fa_IR',
        ],
        'en' => [
            'name' => 'English',
            'script' => 'Latin',
            'native' => 'English',
            'regional' => 'en_GB',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Default Locale
    |--------------------------------------------------------------------------
    |
    | When the application boots, this package will ensure that the locale is
    | set. The default locale is set in config/app.php (we set it to 'fa').
    |
    */

    'useAcceptLanguageHeader' => false,

    'hideDefaultLocaleInURL' => false,

    'useCookie' => false,

    'useSessionLocale' => true,

    'redirectToDefaultLocale' => true,
];
