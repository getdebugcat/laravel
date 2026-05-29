<?php

declare(strict_types=1);

namespace DebugCat\Laravel\Transport;

/**
 * Ships a serialized occurrence payload to a DebugCat ingest endpoint.
 */
interface Transport
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function send(array $payload): void;
}
