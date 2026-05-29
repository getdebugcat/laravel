<?php

declare(strict_types=1);

namespace DebugCat\Laravel\Facades;

use DebugCat\Laravel\Context\ContextProvider;
use DebugCat\Laravel\Report;
use Illuminate\Support\Facades\Facade;
use Throwable;

/**
 * @method static bool isEnabled()
 * @method static Report|null report(Throwable $throwable, ?callable $callback = null)
 * @method static Report|null reportMessage(string $message, string $level = 'info', ?callable $callback = null)
 * @method static \DebugCat\Laravel\DebugCat registerContextProvider(ContextProvider $provider)
 * @method static \DebugCat\Laravel\DebugCat beforeSend(callable $callback)
 *
 * @see \DebugCat\Laravel\DebugCat
 */
class DebugCat extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \DebugCat\Laravel\DebugCat::class;
    }
}
