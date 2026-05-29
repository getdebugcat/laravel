<?php

declare(strict_types=1);

namespace DebugCat\Laravel;

use DebugCat\Laravel\Commands\TestCommand;
use DebugCat\Laravel\Context\EnvironmentContextProvider;
use DebugCat\Laravel\Context\RequestContextProvider;
use DebugCat\Laravel\Context\UserContextProvider;
use DebugCat\Laravel\Support\Backtrace;
use DebugCat\Laravel\Support\Censor;
use DebugCat\Laravel\Transport\HttpTransport;
use DebugCat\Laravel\Transport\Transport;
use Illuminate\Contracts\Bus\Dispatcher;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Foundation\Exceptions\Handler as FoundationHandler;
use Illuminate\Http\Client\Factory as Http;
use Illuminate\Http\Request;
use Illuminate\Support\ServiceProvider;
use Throwable;

class DebugCatServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/debugcat.php', 'debugcat');

        $this->app->singleton(Transport::class, function ($app): Transport {
            $config = $app['config']['debugcat'];

            return new HttpTransport(
                http: $app->make(Http::class),
                host: (string) ($config['host'] ?? ''),
                key: (string) ($config['key'] ?? ''),
                timeout: (int) ($config['timeout'] ?? 5),
            );
        });

        $this->app->singleton(Censor::class, function ($app): Censor {
            $censor = $app['config']['debugcat.censor'] ?? [];

            return new Censor(
                fields: $censor['fields'] ?? [],
                replacement: $censor['replacement'] ?? '[CENSORED]',
            );
        });

        $this->app->singleton(DebugCat::class, function ($app): DebugCat {
            $config = $app['config']['debugcat'];

            $debugcat = new DebugCat(
                transport: $app->make(Transport::class),
                backtrace: new Backtrace(
                    basePath: $config['base_path'] ?? $app->basePath(),
                    inAppExcludes: $config['in_app_excludes'] ?? ['/vendor/'],
                    snippetLines: (int) ($config['snippet_lines'] ?? 15),
                ),
                censor: $app->make(Censor::class),
                dispatcher: $app->make(Dispatcher::class),
                config: $config,
            );

            $this->registerContextProviders($debugcat, $app, $config);

            return $debugcat;
        });
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/debugcat.php' => $this->app->configPath('debugcat.php'),
            ], 'debugcat-config');

            $this->commands([TestCommand::class]);
        }

        $this->registerExceptionHandler();
    }

    /**
     * Hook into Laravel's exception handler so unhandled exceptions are
     * reported automatically — no edits to bootstrap/app.php required.
     */
    protected function registerExceptionHandler(): void
    {
        if (! ($this->app['config']['debugcat.enabled'] ?? false)) {
            return;
        }

        if (! $this->app->bound(ExceptionHandler::class)) {
            return;
        }

        $handler = $this->app->make(ExceptionHandler::class);

        if (! $handler instanceof FoundationHandler) {
            return;
        }

        $handler->reportable(function (Throwable $throwable): void {
            if ($this->shouldReportInCurrentEnvironment()) {
                $this->app->make(DebugCat::class)->report($throwable);
            }
        });
    }

    protected function shouldReportInCurrentEnvironment(): bool
    {
        $environments = $this->app['config']['debugcat.environments'] ?? [];

        return $environments === [] || in_array($this->app->environment(), $environments, true);
    }

    /**
     * @param  array<string, mixed>  $config
     */
    protected function registerContextProviders(DebugCat $debugcat, $app, array $config): void
    {
        $debugcat->registerContextProvider(new EnvironmentContextProvider(
            environment: (string) $app->environment(),
            release: $config['release'] ?? null,
        ));

        $request = $app->runningInConsole() ? null : $app->make(Request::class);

        $debugcat->registerContextProvider(new RequestContextProvider(
            request: $request,
            censor: $app->make(Censor::class),
            sendBody: (bool) ($config['send_request_body'] ?? true),
        ));

        $debugcat->registerContextProvider(new UserContextProvider(
            request: $request,
        ));
    }
}
