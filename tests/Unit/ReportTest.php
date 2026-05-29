<?php

declare(strict_types=1);

use DebugCat\Laravel\Report;
use DebugCat\Laravel\Support\Backtrace;

it('serializes to the exact ingest payload shape', function () {
    $report = Report::fromThrowable(
        new InvalidArgumentException('nope'),
        new Backtrace(basePath: __DIR__, snippetLines: 0),
    );

    $report->mergeContext(['environment' => 'testing'])
        ->setUser(['id' => 7, 'email' => 'a@b.test']);

    $payload = $report->toArray();

    expect($payload)->toHaveKeys([
        'exception_class', 'message', 'level', 'stack_trace', 'context', 'user', 'occurred_at',
    ])
        ->and($payload['exception_class'])->toBe(InvalidArgumentException::class)
        ->and($payload['message'])->toBe('nope')
        ->and($payload['level'])->toBe('error')
        ->and($payload['context'])->toBe(['environment' => 'testing'])
        ->and($payload['user'])->toBe(['id' => 7, 'email' => 'a@b.test'])
        ->and($payload['stack_trace'][0])->toHaveKeys(['file', 'line', 'function', 'class', 'in_app', 'code_snippet'])
        ->and($payload['occurred_at'])->toMatch('/^\d{4}-\d{2}-\d{2}T/');
});

it('emits null context when none was added', function () {
    $report = new Report('Foo\\Bar', 'msg');

    expect($report->toArray()['context'])->toBeNull()
        ->and($report->toArray()['user'])->toBeNull();
});
