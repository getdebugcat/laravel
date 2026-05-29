<?php

declare(strict_types=1);

namespace DebugCat\Laravel\Context;

use DebugCat\Laravel\Report;
use DebugCat\Laravel\Support\Censor;
use Illuminate\Http\Request;

/**
 * Adds HTTP request details (url, method, agent, ip) to the occurrence
 * context, plus censored request body when enabled.
 */
class RequestContextProvider implements ContextProvider
{
    public function __construct(
        protected ?Request $request,
        protected Censor $censor,
        protected bool $sendBody = true,
    ) {}

    public function enrich(Report $report): void
    {
        if ($this->request === null) {
            return;
        }

        $report->mergeContext([
            'url' => $this->request->fullUrl(),
            'http_method' => $this->request->method(),
            'user_agent' => $this->request->userAgent(),
            'ip' => $this->request->ip(),
        ]);

        if ($this->sendBody) {
            $body = $this->censor->scrub($this->request->except(['_token']));

            if ($body !== []) {
                $report->mergeContext(['request_body' => $body]);
            }
        }
    }
}
