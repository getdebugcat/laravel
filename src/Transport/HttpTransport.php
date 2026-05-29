<?php

declare(strict_types=1);

namespace DebugCat\Laravel\Transport;

use Illuminate\Http\Client\Factory as Http;

/**
 * POSTs occurrences to the DebugCat ingest endpoint, authenticating with the
 * per-project key via the X-DebugCat-Key header.
 *
 * The endpoint is fixed to DebugCat's hosted collection — it is intentionally
 * not configurable by consuming applications.
 */
class HttpTransport implements Transport
{
    /**
     * The DebugCat hosted ingest endpoint. Hardcoded by design.
     */
    public const ENDPOINT = 'https://debugcat.co/api/ingest';

    public function __construct(
        protected Http $http,
        protected string $key,
        protected int $timeout = 5,
    ) {}

    public function send(array $payload): void
    {
        $this->http
            ->withHeaders(['X-DebugCat-Key' => $this->key])
            ->acceptJson()
            ->timeout($this->timeout)
            ->post(self::ENDPOINT, $payload);
    }
}
