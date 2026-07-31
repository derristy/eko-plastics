<?php

return [
    'salesdrive' => [
        'url' => env('SALESDRIVE_URL', 'https://api.salesdrive.me'),
        'api_key' => env('SALESDRIVE_API_KEY', ''),
        'webhook_secret' => env('SALESDRIVE_WEBHOOK_SECRET', ''),
    ],

    'dilovod' => [
        'url' => env('DILOVOD_URL', 'https://api.dilovod.ua'),
        'key' => env('DILOVOD_KEY', ''),
        // справочник "Тип особи" в Діловоді, категория Клієнт
        'person_type' => env('DILOVOD_PERSON_TYPE', '1004000000000035'),
        'webhook_secret' => env('DILOVOD_WEBHOOK_SECRET', ''),
        // группа справочника контрагентов, куда попадают клиенты с формы
        'group' => env('DILOVOD_PERSON_GROUP', ''),
    ],

    'telegram' => [
        'token' => env('TELEGRAM_BOT_TOKEN', ''),
        'chat_id' => env('TELEGRAM_CHAT_ID', ''),
    ],
];
