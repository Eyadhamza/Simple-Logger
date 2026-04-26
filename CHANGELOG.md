# Changelog

All notable changes to `PiSpace-Logger` will be documented in this file.

## 2.3.0 — 2026-04-25

### Security
- **Database driver no longer writes raw secrets.** In 2.2.x, `LogPayloadSanitizer` ran inside `LokiDriver` only — meaning the default `database` driver inserted full `Authorization` headers, `password` / `current_password` / `verification_code` body fields, and any decrypted webhook payloads straight into `api_logs`. A DB dump trivially leaked credentials.
  - Sanitization now runs once in `LoggerService::log()`, before `CreateLogJob::dispatchAfterResponse(...)`. Every driver — built-in or custom — receives a redacted record.
  - The redaction policy is now top-level (`simple-logger.redact.{headers,body_keys,max_field_bytes}`) and shared by every driver.
  - Per-driver `redact_headers` / `redact_body_keys` keys remain supported for backward compatibility but are deprecated.
  - The default body-keys list grew to cover `verification_code`, `reset_code`, `pan`, `card_token`, `iv`, `auth_tag`, `ciphertext`. Default headers list grew to cover `x-csrf-token` / `x-xsrf-token`.
- `LogDriverManager` and `LoggerService` now take a shared `LogPayloadSanitizer` via the container, bound by the service provider. `LokiDriver` continues to receive it as defense-in-depth (idempotent — calling `sanitize()` on already-sanitized data is a no-op).

### Upgrade notes
- No action required for existing installs — backward-compatible config fallback resolves the legacy `drivers.loki.redact_*` keys when the new top-level `simple-logger.redact.*` block is absent.
- Recommended: re-publish the config (`php artisan vendor:publish --tag=simple-logger-config --force`) to pick up the broader default redact list.

## 2.2.0 — 2026-04-24

### Added
- Pluggable driver system behind `CreateLogJob`. Select via `simple-logger.default` or `SIMPLE_LOGGER_DRIVER` env.
  - `database` — the original behavior (insert into `api_logs`).
  - `loki` — emits a single structured JSON line on the configured Laravel log channel (default `stderr`). Useful on serverless where CloudWatch forwards stderr to Loki/Grafana Cloud.
  - `stack` — fans out to multiple drivers; a failure in one driver is logged and does not block the others.
  - `null` — disables all sinks.
- `LogPayloadSanitizer` with configurable redaction for sensitive headers (`authorization`, `cookie`, `x-api-key`, …) and body keys (`password`, `token`, `otp`, `card_number`, …). Fields that exceed `max_field_bytes` (default 8 KB) become a `{_truncated, _original_bytes, _preview}` stub.
- `LogDriverManager` singleton resolved from container; custom drivers can be added by binding into the manager.

### Changed
- `CreateLogJob::handle` now resolves `LogDriverManager` from the container instead of calling `ApiLog::create` directly.
- `CreateLogJob` no longer implements `ShouldQueue`. `LoggerService` already dispatches via `dispatchAfterResponse`, which runs the handler in the same PHP process after the HTTP response is sent — zero user-perceived latency, no SQS round-trip, no worker Lambda invocation. Drop the `InteractsWithQueue` / `Queueable` traits too.
- `config/simple-logger.php` gained `default` + `drivers` sections (was empty).

### Upgrade notes
- Existing installs keep current behavior without action — `default` falls back to `database`.
- To mirror to Loki in addition to DB, publish the config and set `SIMPLE_LOGGER_DRIVER=stack`.
- To go Loki-only, set `SIMPLE_LOGGER_DRIVER=loki`.
- Jobs previously handled on the queue worker now run inline in the request Lambda after response. Expect a small increase in request-Lambda duration (≈1 ms for `loki`, 20-80 ms for `database`) and a matching decrease in SQS + worker-Lambda usage. If you need the old queued behavior (e.g. DB-write retry-on-failure), subclass `CreateLogJob` with `implements ShouldQueue` and dispatch your subclass instead.
