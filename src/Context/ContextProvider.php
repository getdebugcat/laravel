<?php

declare(strict_types=1);

namespace DebugCat\Laravel\Context;

use DebugCat\Laravel\Report;

/**
 * Enriches a {@see Report} with environment-specific data (request, user, …)
 * just before it is sent.
 */
interface ContextProvider
{
    public function enrich(Report $report): void;
}
