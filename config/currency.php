<?php

return [

    'base' => 'USD',

    /*
    |--------------------------------------------------------------------------
    | Supported account currencies (ISO 4217)
    |--------------------------------------------------------------------------
    */
    'supported' => [
        'USD',
        'CAD',
        'EUR',
        'GBP',
        'AUD',
        'CHF',
        'JPY',
        'MXN',
        'NZD',
        'SEK',
        'NOK',
        'DKK',
        'SGD',
        'HKD',
    ],

    'frankfurter' => [
        'base_url' => 'https://api.frankfurter.dev/v1',
    ],

    /*
    | Open-access tier: attribution required if used as failover.
    | @see https://www.exchangerate-api.com/docs/free
    */
    'exchange_rate_api' => [
        'base_url' => 'https://open.er-api.com/v6/latest/USD',
    ],

    'cache_key' => 'fx.to_usd',

];
