<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Log Driver
    |--------------------------------------------------------------------------
    |
    | The driver used to persist API logs. One of the keys in `drivers`
    | below, or `null` to disable all sinks.
    |
    | Supported: "database", "loki", "stack", "null"
    */
    'default' => env('SIMPLE_LOGGER_DRIVER', 'database'),

    /*
    |--------------------------------------------------------------------------
    | Drivers
    |--------------------------------------------------------------------------
    */
    'drivers' => [

        'database' => [
            'driver' => 'database',
        ],

        /*
        | Emits a structured JSON line to the given Laravel log channel. Use
        | `stderr` on Lambda (captured by CloudWatch and forwarded to Loki),
        | or point at a dedicated Monolog Loki handler channel for direct
        | push on long-running servers.
        */
        'loki' => [
            'driver'          => 'loki',
            'channel'         => env('SIMPLE_LOGGER_LOKI_CHANNEL', 'stderr'),
            'max_field_bytes' => (int) env('SIMPLE_LOGGER_MAX_FIELD_BYTES', 8192),

            'redact_headers' => [
                'authorization', 'cookie', 'set-cookie',
                'x-api-key', 'x-auth-token', 'proxy-authorization',
            ],

            'redact_body_keys' => [
                'password', 'password_confirmation', 'current_password', 'new_password',
                'token', 'access_token', 'refresh_token',
                'api_key', 'api_token', 'secret', 'client_secret',
                'otp', 'otp_code', 'pin', 'cvv', 'cvc',
                'card_number', 'credit_card',
            ],
        ],

        /*
        | Fans out to multiple drivers in order. Failure in any child driver
        | is caught so one broken sink never blocks the others.
        */
        'stack' => [
            'driver'  => 'stack',
            'drivers' => ['database', 'loki'],
        ],

        'null' => [
            'driver' => 'null',
        ],
    ],

];
