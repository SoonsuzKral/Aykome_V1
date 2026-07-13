<?php

return [

    'name' => env('APP_NAME', 'AYKOME'),

    'env' => env('APP_ENV', 'production'),

    'debug' => (bool) env('APP_DEBUG', false),

    'url' => env('APP_URL', 'http://localhost'),

    'timezone' => 'Europe/Istanbul',

    'locale' => env('APP_LOCALE', 'tr'),

    'fallback_locale' => env('APP_FALLBACK_LOCALE', 'tr'),

    'faker_locale' => env('APP_FAKER_LOCALE', 'tr_TR'),

    'cipher' => 'AES-256-CBC',

    'key' => env('APP_KEY'),

    'previous_keys' => [
        ...array_filter(
            explode(',', (string) env('APP_PREVIOUS_KEYS', ''))
        ),
    ],

    'maintenance' => [
        'driver' => env('APP_MAINTENANCE_DRIVER', 'file'),
        'store' => env('APP_MAINTENANCE_STORE', 'database'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Firma Bilgileri (HGB Bilişim)
    |--------------------------------------------------------------------------
    */
    'company' => [
        'name' => 'HGB Bilişim Sistemleri Tic. Ltd. Şti.',
        'short' => 'HGB Bilişim',
        'address' => 'Toros Mah. Aydın Gün Cad. Zülal Apt. A Blok No: 5/1 İç Kapı No: 19 Çukurova / Adana',
        'tax_office' => 'Ziyapaşa V.D.',
        'tax_no' => '4621097486',
        'phone' => '0533 361 9154',
        'mersis' => '046210974860001',
        'email' => 'destek@hgbilisim.com',
        'website' => 'https://hgbilisim.com',
    ],
];