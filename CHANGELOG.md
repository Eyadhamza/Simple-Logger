# Changelog

All notable changes to `PiSpace-Logger` will be documented in this file.

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
