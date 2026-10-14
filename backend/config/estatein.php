<?php

return [

    /*
    | The seeded admin account. The defaults are the sample credentials from the design doc;
    | set ADMIN_EMAIL / ADMIN_PASSWORD in .env to use your own.
    */
    'admin' => [
        'name' => 'Estatein Admin',
        'email' => env('ADMIN_EMAIL', 'admin@estatein.test'),
        'password' => env('ADMIN_PASSWORD') ?: 'password',
    ],

    'images' => [
        'max_kilobytes' => 5120,
        'mimes' => ['jpeg', 'png', 'webp'],
        'mimetypes' => ['image/jpeg', 'image/png', 'image/webp'],
        'max_per_property' => 20,
    ],

    'properties' => [
        'per_page' => 9,
        'max_per_page' => 50,
        'max_features' => 30,
    ],

];
