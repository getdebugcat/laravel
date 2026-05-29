<?php

declare(strict_types=1);

namespace DebugCat\Laravel\Commands;

use DebugCat\Laravel\DebugCat;
use Illuminate\Console\Command;
use RuntimeException;

/**
 * Sends a deliberate test exception so you can confirm the SDK is wired up
 * and your project key reaches DebugCat.
 */
class TestCommand extends Command
{
    protected $signature = 'debugcat:test';

    protected $description = 'Send a test exception to DebugCat to verify your configuration';

    public function handle(DebugCat $debugcat): int
    {
        if (! $debugcat->isEnabled()) {
            $this->components->error('DebugCat is disabled. Set DEBUGCAT_KEY (and DEBUGCAT_ENABLED=true) in your .env.');

            return self::FAILURE;
        }

        $this->components->info('Sending a test exception to DebugCat…');

        $report = $debugcat->report(
            new RuntimeException('This is a test exception from debugcat:test 🐛'),
        );

        if ($report === null) {
            $this->components->error('The test exception could not be sent. Check your DEBUGCAT_KEY.');

            return self::FAILURE;
        }

        $this->components->info('Sent! Check your DebugCat project — the issue should appear shortly.');

        return self::SUCCESS;
    }
}
