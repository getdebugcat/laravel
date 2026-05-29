<?php

declare(strict_types=1);

use DebugCat\Laravel\DebugCat;
use DebugCat\Laravel\Jobs\SendOccurrence;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    Http::fake(['*' => Http::response(['status' => 'accepted'], 202)]);
});

it('posts an occurrence to /api/ingest with the project key header', function () {
    app(DebugCat::class)->report(new RuntimeException('kaboom'));

    Http::assertSent(function ($request) {
        return $request->url() === 'https://debugcat.test/api/ingest'
            && $request->method() === 'POST'
            && $request->hasHeader('X-DebugCat-Key', 'test-key')
            && $request['exception_class'] === RuntimeException::class
            && $request['message'] === 'kaboom'
            && is_array($request['stack_trace'])
            && $request['context']['environment'] === 'testing';
    });
});

it('is a no-op when disabled', function () {
    config()->set('debugcat.enabled', false);

    $report = app(DebugCat::class)->report(new RuntimeException('ignored'));

    expect($report)->toBeNull();
    Http::assertNothingSent();
});

it('is a no-op when no key is configured', function () {
    config()->set('debugcat.key', null);

    $report = app(DebugCat::class)->report(new RuntimeException('ignored'));

    expect($report)->toBeNull();
    Http::assertNothingSent();
});

it('dispatches a queued job instead of sending inline when queue is enabled', function () {
    Bus::fake();
    config()->set('debugcat.queue.enabled', true);

    // Rebuild the singleton so it picks up the queue config.
    app()->forgetInstance(DebugCat::class);

    app(DebugCat::class)->report(new RuntimeException('queued'));

    Bus::assertDispatched(SendOccurrence::class, function (SendOccurrence $job) {
        return $job->payload['exception_class'] === RuntimeException::class;
    });
    Http::assertNothingSent();
});

it('applies beforeSend callbacks', function () {
    app(DebugCat::class)->beforeSend(fn ($report) => $report->setLevel('fatal'));

    app(DebugCat::class)->report(new RuntimeException('boom'));

    Http::assertSent(fn ($request) => $request['level'] === 'fatal');
});

it('reports an ad-hoc message with a synthetic frame', function () {
    app(DebugCat::class)->reportMessage('something happened', 'warning');

    Http::assertSent(function ($request) {
        return $request['message'] === 'something happened'
            && $request['level'] === 'warning'
            && count($request['stack_trace']) === 1;
    });
});
