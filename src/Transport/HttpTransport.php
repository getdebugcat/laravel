<?php

declare(strict_types=1);

namespace DebugCat\Laravel\Transport;

use Illuminate\Http\Client\Factory as Http;

/**
 * POSTs occurrences to {host}/api/ingest, authenticating with the
 * per-project key via the X-DebugCat-Key header.
 */
class HttpTransport implements Transport
{
    public function __construct(
        protected Http $http,
        protected string $host,
        protected string $key,
        protected int $timeout = 5,
    ) {}

    public function send(array $payload): void
    {
        $this->http
            ->withHeaders(['X-DebugCat-Key' => $this->key])
            ->acceptJson()
            ->timeout($this->timeout)
            ->post($this->endpoint(), $payload);
    }

    protected function endpoint(): string
    {
        return rtrim($this->host, '/').'/api/ingest';
    }
}
