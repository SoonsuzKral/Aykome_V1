<?php

return [
    'google_maps_api_key' => env('GOOGLE_MAPS_API_KEY'),

    /*
    | Makam (Başkan / Başkan Yrd.) rolleri.
    | Bu rollere sahip kullanıcılar sisteme girdiğinde anasayfaları
    | "Makam Masası" (Önümdeki Bekleyen İmzalar) olur.
    */
    'makam_roles' => [
        'municipality-makam',
        'municipality-admin',
        'super-admin',
    ],
];

