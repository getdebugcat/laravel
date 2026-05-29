<?php

declare(strict_types=1);

namespace DebugCat\Laravel\Tests;

use DebugCat\Laravel\DebugCatServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [DebugCatServiceProvider::class];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('debugcat.enabled', true);
        $app['config']->set('debugcat.key', 'test-key');
        $app['config']->set('debugcat.environments', []); // report from all envs in tests
    }
}
