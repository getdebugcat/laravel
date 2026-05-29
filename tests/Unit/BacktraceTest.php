<?php

declare(strict_types=1);

use DebugCat\Laravel\Support\Backtrace;

it('produces DebugCat-shaped frames with the throwable location first', function () {
    $backtrace = new Backtrace(basePath: __DIR__, snippetLines: 5);

    $line = __LINE__ + 1;
    $exception = new RuntimeException('boom');

    $frames = $backtrace->fromThrowable($exception);

    expect($frames)->not->toBeEmpty()
        ->and($frames[0]->file)->toBe(__FILE__)
        ->and($frames[0]->line)->toBe($line)
        ->and($frames[0]->toArray())->toHaveKeys(['file', 'line', 'function', 'class', 'in_app', 'code_snippet']);
});

it('flags frames inside base_path as in_app and excludes vendor', function () {
    $backtrace = new Backtrace(
        basePath: '/app',
        inAppExcludes: ['/vendor/'],
        snippetLines: 0,
    );

    $reflect = new ReflectionMethod($backtrace, 'isApplicationFrame');

    expect($reflect->invoke($backtrace, '/app/src/Foo.php'))->toBeTrue()
        ->and($reflect->invoke($backtrace, '/app/vendor/laravel/Foo.php'))->toBeFalse()
        ->and($reflect->invoke($backtrace, '/other/Foo.php'))->toBeFalse();
});

it('attaches a code snippet to in-app frames only', function () {
    $backtrace = new Backtrace(basePath: __DIR__, snippetLines: 5);

    $frames = $backtrace->fromThrowable(new RuntimeException('boom'));

    expect($frames[0]->inApp)->toBeTrue()
        ->and($frames[0]->codeSnippet)->toBeArray()
        ->and($frames[0]->codeSnippet)->not->toBeEmpty();
});
