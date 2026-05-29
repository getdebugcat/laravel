<?php

declare(strict_types=1);

namespace DebugCat\Laravel\Context;

use DebugCat\Laravel\Report;

/**
 * Adds the app environment and release identifier to the occurrence context.
 */
class EnvironmentContextProvider implements ContextProvider
{
    public function __construct(
        protected string $environment,
        protected ?string $release = null,
    ) {}

    public function enrich(Report $report): void
    {
        $report->mergeContext(array_filter([
            'environment' => $this->environment,
            'release' => $this->release,
        ], fn ($value) => $value !== null));
    }
}
