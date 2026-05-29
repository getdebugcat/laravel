# DebugCat SDK for Laravel

The official Laravel SDK for [DebugCat](https://debugcat.test) — capture exceptions in your
Laravel app and ship them to your DebugCat project for grouping, alerting, and triage.

Inspired by the architecture of Spatie's Flare client: a small framework-agnostic core
(`Report` → context providers → transport) wired into Laravel's exception handler.

## How it works

```
Throwable ──► DebugCat::report() ──► Report (built from backtrace)
                                       │
                  context providers ───┤  request · user · environment/release
                                       │
                      beforeSend hooks ┤  your custom mutations
                                       │
                              Transport ──► POST {host}/api/ingest
                                            header: X-DebugCat-Key
```

Unhandled exceptions are reported automatically — the service provider registers a
`reportable` callback on Laravel's exception handler, so you don't touch `bootstrap/app.php`.

## Installation

```bash
composer require debugcat/laravel
php artisan vendor:publish --tag=debugcat-config   # optional
```

The service provider and `DebugCat` facade are auto-discovered.

## Configuration

Add your project's ingest key (shown once when you create the project in DebugCat):

```dotenv
DEBUGCAT_KEY=your-40-char-project-key
DEBUGCAT_HOST=https://debugcat.test          # your DebugCat install
DEBUGCAT_ENABLED=true
DEBUGCAT_ENVIRONMENTS=production,staging      # report only from these (empty = all)
DEBUGCAT_RELEASE="${APP_VERSION}"            # optional deploy/commit marker
DEBUGCAT_QUEUE=false                          # true = send via the queue
```

All options live in `config/debugcat.php`.

## Verify your setup

```bash
php artisan debugcat:test
```

This sends a deliberate test exception. It should appear in your DebugCat project within seconds.

## Manual reporting

```php
use DebugCat\Laravel\Facades\DebugCat;

try {
    riskyOperation();
} catch (\Throwable $e) {
    // Report a caught exception without re-throwing it.
    DebugCat::report($e);
}

// Attach one-off context to a specific report.
DebugCat::report($e, function (\DebugCat\Laravel\Report $report) {
    $report->setLevel('warning')
        ->mergeContext(['order_id' => $order->id]);
});

// Log an ad-hoc message (no exception).
DebugCat::reportMessage('Payment webhook arrived out of order', 'warning');
```

### Global enrichment

Add context to every report from a service provider's `boot()`:

```php
use DebugCat\Laravel\Facades\DebugCat;

DebugCat::beforeSend(function (\DebugCat\Laravel\Report $report) {
    $report->mergeContext(['tenant' => tenant()?->id]);
});
```

## What gets sent

Each occurrence matches DebugCat's ingest contract:

| Field            | Source                                                            |
| ---------------- | ---------------------------------------------------------------- |
| `exception_class`| `get_class($throwable)`                                          |
| `message`        | `$throwable->getMessage()`                                       |
| `level`          | `error` by default; override per-report                          |
| `stack_trace[]`  | `file`, `line`, `function`, `class`, `in_app`, `code_snippet`    |
| `context`        | url, http method, user agent, ip, environment, release, body     |
| `user`           | id, email, name of the authenticated user                        |
| `occurred_at`    | ISO-8601 timestamp                                               |

Frames inside your `base_path` (excluding `/vendor/`) are flagged `in_app`; those frames drive
issue grouping on the server and are the only ones that carry source snippets.

## Privacy & censoring

Sensitive fields (`password`, `token`, `secret`, …) are redacted from the request body, headers,
and user payload before anything leaves your app. Extend the list in `config/debugcat.php` under
`censor.fields`, or set `send_request_body` to `false` to omit request input entirely.

## Reliability

Reporting never throws into your application and never blocks it for long: HTTP calls use a short
timeout, all failures are swallowed, and you can offload delivery to the queue with
`DEBUGCAT_QUEUE=true`.

## Testing

```bash
composer install
./vendor/bin/pest
```
