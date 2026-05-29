<?php

declare(strict_types=1);

namespace DebugCat\Laravel\Jobs;

use DebugCat\Laravel\Transport\Transport;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

/**
 * Delivers a captured occurrence off the request cycle. Failures are
 * swallowed: a broken error tracker must never break the host app.
 */
class SendOccurrence implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 1;

    /**
     * @param  array<string, mixed>  $payload
     */
    public function __construct(
        public array $payload,
    ) {}

    public function handle(Transport $transport): void
    {
        try {
            $transport->send($this->payload);
        } catch (Throwable) {
            // Never let reporting failures cascade into the queue worker.
        }
    }
}
