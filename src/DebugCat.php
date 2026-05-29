<?php

declare(strict_types=1);

namespace DebugCat\Laravel;

use DebugCat\Laravel\Context\ContextProvider;
use DebugCat\Laravel\Jobs\SendOccurrence;
use DebugCat\Laravel\Support\Backtrace;
use DebugCat\Laravel\Support\Censor;
use DebugCat\Laravel\Transport\Transport;
use Illuminate\Contracts\Bus\Dispatcher;
use Throwable;

/**
 * The SDK entry point: captures a Throwable, enriches it with context,
 * and ships it to DebugCat. Resolve via the container or the DebugCat facade.
 */
class DebugCat
{
    /** @var list<ContextProvider> */
    protected array $contextProviders = [];

    /** @var array<int, callable(Report): void> */
    protected array $reportCallbacks = [];

    /**
     * @param  array{enabled?: bool, key?: ?string, queue?: array{enabled?: bool, connection?: ?string, queue?: ?string}}  $config
     */
    public function __construct(
        protected Transport $transport,
        protected Backtrace $backtrace,
        protected Censor $censor,
        protected Dispatcher $dispatcher,
        protected array $config = [],
    ) {}

    /**
     * Register a provider that enriches every report just before it is sent.
     */
    public function registerContextProvider(ContextProvider $provider): self
    {
        $this->contextProviders[] = $provider;

        return $this;
    }

    /**
     * Register a callback to mutate every report (add custom context, change
     * the level, etc.) before sending.
     *
     * @param  callable(Report): void  $callback
     */
    public function beforeSend(callable $callback): self
    {
        $this->reportCallbacks[] = $callback;

        return $this;
    }

    /**
     * Whether the SDK is configured to send anything at all.
     */
    public function isEnabled(): bool
    {
        return ($this->config['enabled'] ?? false) && ! empty($this->config['key']);
    }

    /**
     * Capture and send an exception. Returns the built Report (or null when
     * disabled). Any internal failure is swallowed.
     *
     * @param  callable(Report): void|null  $callback  last-chance hook to mutate this report
     */
    public function report(Throwable $throwable, ?callable $callback = null): ?Report
    {
        if (! $this->isEnabled()) {
            return null;
        }

        try {
            $report = Report::fromThrowable($throwable, $this->backtrace);

            foreach ($this->contextProviders as $provider) {
                $provider->enrich($report);
            }

            foreach ($this->reportCallbacks as $reportCallback) {
                $reportCallback($report);
            }

            if ($callback !== null) {
                $callback($report);
            }

            $this->dispatch($report->toArray());

            return $report;
        } catch (Throwable) {
            // Reporting must never throw into the host application.
            return null;
        }
    }

    /**
     * Send an ad-hoc message (no exception) at the given level.
     */
    public function reportMessage(string $message, string $level = 'info', ?callable $callback = null): ?Report
    {
        if (! $this->isEnabled()) {
            return null;
        }

        try {
            $report = new Report(
                exceptionClass: 'DebugCat\\Message',
                message: $message,
                level: $level,
            );
            // A message has no real stack; synthesize a single frame so the
            // payload satisfies the ingest contract's required stack_trace.
            $report->mergeContext([]);

            foreach ($this->contextProviders as $provider) {
                $provider->enrich($report);
            }

            if ($callback !== null) {
                $callback($report);
            }

            $payload = $report->toArray();
            $payload['stack_trace'] = $this->syntheticFrame();

            $this->dispatch($payload);

            return $report;
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    protected function dispatch(array $payload): void
    {
        $queue = $this->config['queue'] ?? [];

        if (($queue['enabled'] ?? false) === true) {
            $job = new SendOccurrence($payload);

            if (! empty($queue['connection'])) {
                $job->onConnection($queue['connection']);
            }

            if (! empty($queue['queue'])) {
                $job->onQueue($queue['queue']);
            }

            $this->dispatcher->dispatch($job);

            return;
        }

        $this->transport->send($payload);
    }

    /**
     * @return list<array<string, mixed>>
     */
    protected function syntheticFrame(): array
    {
        $caller = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 4)[3] ?? [];

        return [[
            'file' => $caller['file'] ?? 'unknown',
            'line' => $caller['line'] ?? 0,
            'function' => $caller['function'] ?? null,
            'class' => $caller['class'] ?? null,
            'in_app' => true,
            'code_snippet' => null,
        ]];
    }
}
