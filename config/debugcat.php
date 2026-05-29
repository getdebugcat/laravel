<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Project Ingest Key
    |--------------------------------------------------------------------------
    |
    | The per-project key shown once when you create a project in DebugCat.
    | It is sent on every request via the "X-DebugCat-Key" header. Leave this
    | empty to disable reporting (e.g. on local/dev machines without a key).
    |
    */

    'key' => env('DEBUGCAT_KEY'),

    /*
    |--------------------------------------------------------------------------
    | Enabled
    |--------------------------------------------------------------------------
    |
    | Master switch. When false, the SDK is fully inert: no handler is
    | registered and report() becomes a no-op. Reporting also requires a key.
    |
    */

    'enabled' => env('DEBUGCAT_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Environments to Report
    |--------------------------------------------------------------------------
    |
    | Only report when the current app environment is in this list. Leave the
    | array empty to report from every environment.
    |
    */

    'environments' => array_values(array_filter(
        explode(',', (string) env('DEBUGCAT_ENVIRONMENTS', 'production,staging'))
    )),

    /*
    |--------------------------------------------------------------------------
    | Release
    |--------------------------------------------------------------------------
    |
    | An optional version/commit identifier attached to every occurrence so you
    | can tell which deploy a regression came from.
    |
    */

    'release' => env('DEBUGCAT_RELEASE'),

    /*
    |--------------------------------------------------------------------------
    | Queue Sending
    |--------------------------------------------------------------------------
    |
    | When true, occurrences are dispatched to the queue instead of being sent
    | inline during the request. Requires a running worker. The "connection"
    | and "queue" keys let you route reports to a dedicated worker.
    |
    */

    'queue' => [
        'enabled' => env('DEBUGCAT_QUEUE', false),
        'connection' => env('DEBUGCAT_QUEUE_CONNECTION'),
        'queue' => env('DEBUGCAT_QUEUE_NAME'),
    ],

    /*
    |--------------------------------------------------------------------------
    | HTTP Timeout (seconds)
    |--------------------------------------------------------------------------
    |
    | Kept short so a slow/unreachable DebugCat never blocks your application.
    |
    */

    'timeout' => env('DEBUGCAT_TIMEOUT', 5),

    /*
    |--------------------------------------------------------------------------
    | Code Snippets
    |--------------------------------------------------------------------------
    |
    | Number of source lines to capture around each application stack frame.
    | Set to 0 to skip reading source files entirely.
    |
    */

    'snippet_lines' => env('DEBUGCAT_SNIPPET_LINES', 15),

    /*
    |--------------------------------------------------------------------------
    | In-App Detection
    |--------------------------------------------------------------------------
    |
    | The "base_path" is treated as the root of your application code. Frames
    | inside it are flagged "in_app" (and drive issue grouping), except those
    | matching one of the excluded path fragments below (typically vendor).
    |
    */

    'base_path' => null, // defaults to base_path() at runtime

    'in_app_excludes' => [
        '/vendor/',
    ],

    /*
    |--------------------------------------------------------------------------
    | Censoring
    |--------------------------------------------------------------------------
    |
    | Field names whose values are redacted before leaving the application.
    | Matching is case-insensitive and applied to request body fields,
    | headers, and the authenticated user payload.
    |
    */

    'censor' => [
        'fields' => [
            'password',
            'password_confirmation',
            'current_password',
            'secret',
            'token',
            'api_key',
            'authorization',
            'cookie',
            'credit_card',
            'cvv',
        ],
        'replacement' => '[CENSORED]',
    ],

    /*
    |--------------------------------------------------------------------------
    | Capture Request Body
    |--------------------------------------------------------------------------
    |
    | Whether to include (censored) request input in the occurrence context.
    |
    */

    'send_request_body' => env('DEBUGCAT_SEND_REQUEST_BODY', true),

];
