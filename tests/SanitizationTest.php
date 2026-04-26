<?php

use PiSpace\Logger\Core\Support\LogPayloadSanitizer;

/*
 * Regression tests for the 2.3.0 fix: every driver — not only Loki — must
 * receive a record with sensitive headers and body keys redacted.
 */

it('redacts sensitive headers regardless of casing', function () {
    $sanitizer = new LogPayloadSanitizer(
        maxFieldBytes: 8192,
        redactHeaders: ['authorization', 'cookie', 'x-api-key'],
        redactBodyKeys: [],
    );

    $clean = $sanitizer->sanitize([
        'method' => 'POST',
        'endpoint' => 'v1/auth/login',
        'headers' => [
            'Authorization' => ['Bearer secret-jwt'],
            'Cookie'        => ['XSRF-TOKEN=abc; laravel_session=xyz'],
            'X-Api-Key'     => ['k-1234'],
            'Content-Type'  => ['application/json'],
        ],
        'request_payload' => [],
    ]);

    expect($clean['headers']['Authorization'])->toBe('[REDACTED]');
    expect($clean['headers']['Cookie'])->toBe('[REDACTED]');
    expect($clean['headers']['X-Api-Key'])->toBe('[REDACTED]');
    expect($clean['headers']['Content-Type'])->toBe(['application/json']);
});

it('redacts sensitive body keys recursively', function () {
    $sanitizer = new LogPayloadSanitizer(
        maxFieldBytes: 8192,
        redactHeaders: [],
        redactBodyKeys: [
            'password', 'current_password', 'verification_code',
            'token', 'card_number',
        ],
    );

    $clean = $sanitizer->sanitize([
        'method' => 'POST',
        'endpoint' => 'v1/auth/login',
        'headers' => [],
        'request_payload' => [
            'phone_number' => '+966500000000',
            'password' => 'hunter2',
            'nested' => [
                'verification_code' => '123456',
                'card_number' => '4242424242424242',
                'safe_field' => 'keep me',
            ],
            'tokens' => [
                ['token' => 'abc', 'kind' => 'fcm'],
                ['token' => 'def', 'kind' => 'apns'],
            ],
        ],
    ]);

    expect($clean['request_payload']['phone_number'])->toBe('+966500000000');
    expect($clean['request_payload']['password'])->toBe('[REDACTED]');
    expect($clean['request_payload']['nested']['verification_code'])->toBe('[REDACTED]');
    expect($clean['request_payload']['nested']['card_number'])->toBe('[REDACTED]');
    expect($clean['request_payload']['nested']['safe_field'])->toBe('keep me');
    expect($clean['request_payload']['tokens'][0]['token'])->toBe('[REDACTED]');
    expect($clean['request_payload']['tokens'][1]['token'])->toBe('[REDACTED]');
});

it('is idempotent — re-sanitizing already-clean data is a no-op', function () {
    $sanitizer = new LogPayloadSanitizer(
        maxFieldBytes: 8192,
        redactHeaders: ['authorization'],
        redactBodyKeys: ['password'],
    );

    $first = $sanitizer->sanitize([
        'method' => 'POST',
        'endpoint' => 'v1/x',
        'headers' => ['Authorization' => ['Bearer secret']],
        'request_payload' => ['password' => 'hunter2', 'name' => 'kept'],
    ]);

    $second = $sanitizer->sanitize($first);

    expect($second)->toBe($first);
});

it('truncates oversize fields with a marker', function () {
    $sanitizer = new LogPayloadSanitizer(
        maxFieldBytes: 32,
        redactHeaders: [],
        redactBodyKeys: [],
    );

    $big = str_repeat('A', 1000);
    $clean = $sanitizer->sanitize([
        'method' => 'POST',
        'endpoint' => 'v1/x',
        'headers' => [],
        'request_payload' => $big,
    ]);

    expect($clean['request_payload'])
        ->toHaveKey('_truncated', true)
        ->toHaveKey('_original_bytes', 1000)
        ->toHaveKey('_preview');
});

it('LoggerService sanitizes before dispatching to drivers', function () {
    // Top-level redact config — the new policy should be picked up by the
    // service-provider singleton.
    config()->set('simple-logger.redact', [
        'max_field_bytes' => 8192,
        'headers'         => ['authorization'],
        'body_keys'       => ['password', 'verification_code'],
    ]);

    $sanitizer = app(LogPayloadSanitizer::class);

    $clean = $sanitizer->sanitize([
        'method' => 'POST',
        'endpoint' => 'v1/auth/login',
        'headers' => ['Authorization' => ['Bearer leaked']],
        'request_payload' => [
            'password' => 'hunter2',
            'verification_code' => '123456',
            'phone_number' => '+966500000000',
        ],
    ]);

    expect($clean['headers']['Authorization'])->toBe('[REDACTED]');
    expect($clean['request_payload']['password'])->toBe('[REDACTED]');
    expect($clean['request_payload']['verification_code'])->toBe('[REDACTED]');
    expect($clean['request_payload']['phone_number'])->toBe('+966500000000');
});

it('falls back to legacy drivers.loki.redact_* when top-level config absent', function () {
    config()->set('simple-logger.redact', null);
    config()->set('simple-logger.drivers.loki.redact_headers', ['authorization']);
    config()->set('simple-logger.drivers.loki.redact_body_keys', ['legacy_secret']);

    // Re-resolve the singleton against the legacy config.
    app()->forgetInstance(LogPayloadSanitizer::class);

    $sanitizer = app(LogPayloadSanitizer::class);
    $clean = $sanitizer->sanitize([
        'method' => 'POST',
        'endpoint' => 'v1/x',
        'headers' => ['Authorization' => ['Bearer x']],
        'request_payload' => ['legacy_secret' => 'top-secret', 'name' => 'kept'],
    ]);

    expect($clean['headers']['Authorization'])->toBe('[REDACTED]');
    expect($clean['request_payload']['legacy_secret'])->toBe('[REDACTED]');
    expect($clean['request_payload']['name'])->toBe('kept');
});
