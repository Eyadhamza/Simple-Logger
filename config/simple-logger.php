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
    | Redaction (applies to ALL drivers)
    |--------------------------------------------------------------------------
    |
    | SECURITY: header / body keys listed here are stripped before the log
    | row is handed to a driver. Previously redaction lived under
    | `drivers.loki` only — meaning the database driver wrote raw
    | Authorization headers and password fields straight into `api_logs`.
    | Now LoggerService applies these lists once, up-front, and every driver
    | (database, loki, stack, custom) sees only the sanitized record.
    |
    | Per-driver `redact_headers` / `redact_body_keys` keys remain supported
    | for backward compatibility but are deprecated — set them here.
    */
    'redact' => [
        'max_field_bytes' => (int) env('SIMPLE_LOGGER_MAX_FIELD_BYTES', 8192),

        'headers' => [
            'authorization', 'cookie', 'set-cookie',
            'x-api-key', 'x-auth-token', 'proxy-authorization',
            'x-csrf-token', 'x-xsrf-token',
        ],

        'body_keys' => [
            // Auth credentials
            'password', 'password_confirmation', 'current_password', 'new_password',
            'token', 'access_token', 'refresh_token', 'remember_token',
            'api_key', 'api_token', 'secret', 'client_secret',
            // OTP / verification material
            'otp', 'otp_code', 'pin', 'verification_code', 'reset_code',
            // Payment instruments — never log even masked values
            'cvv', 'cvc', 'card_number', 'credit_card', 'pan', 'card_token',
            // Encrypted webhook envelopes — no value in storing them post-decrypt
            'iv', 'authTag', 'auth_tag', 'ciphertext',
        ],
    ],

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
            'driver'  => 'loki',
            'channel' => env('SIMPLE_LOGGER_LOKI_CHANNEL', 'stderr'),
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
