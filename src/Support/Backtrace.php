<?php

declare(strict_types=1);

namespace DebugCat\Laravel\Support;

use DebugCat\Laravel\Frame;
use Throwable;

/**
 * Turns a Throwable's trace into DebugCat-shaped {@see Frame} objects,
 * flagging in-app frames and (optionally) attaching source snippets.
 */
class Backtrace
{
    /**
     * @param  list<string>  $inAppExcludes  path fragments that demote a frame to non-app (e.g. "/vendor/")
     */
    public function __construct(
        protected string $basePath,
        protected array $inAppExcludes = ['/vendor/'],
        protected int $snippetLines = 15,
    ) {}

    /**
     * @return list<Frame>
     */
    public function fromThrowable(Throwable $throwable): array
    {
        // The throwable's own location is the deepest (top) frame; getTrace()
        // does not include it, so we prepend it manually.
        $frames = [];

        $frames[] = $this->makeFrame(
            file: $throwable->getFile(),
            line: $throwable->getLine(),
        );

        foreach ($throwable->getTrace() as $trace) {
            // A trace entry's file/line points at the *caller*; its
            // class/function describe what was called there.
            if (! isset($trace['file'])) {
                continue;
            }

            $frames[] = $this->makeFrame(
                file: $trace['file'],
                line: $trace['line'] ?? 0,
                function: $trace['function'] ?? null,
                class: $trace['class'] ?? null,
            );
        }

        return $frames;
    }

    protected function makeFrame(string $file, int $line, ?string $function = null, ?string $class = null): Frame
    {
        $inApp = $this->isApplicationFrame($file);

        return new Frame(
            file: $file,
            line: $line,
            function: $function,
            class: $class,
            inApp: $inApp,
            // Only read source for app frames — vendor snippets are noise and
            // reading every file is needless I/O.
            codeSnippet: $inApp ? $this->snippet($file, $line) : null,
        );
    }

    protected function isApplicationFrame(string $file): bool
    {
        $normalized = str_replace('\\', '/', $file);

        if (! str_starts_with($normalized, str_replace('\\', '/', $this->basePath))) {
            return false;
        }

        foreach ($this->inAppExcludes as $fragment) {
            if (str_contains($normalized, $fragment)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Read up to {@see $snippetLines} lines centered on $line.
     *
     * @return array<int, string>|null line number => source line
     */
    protected function snippet(string $file, int $line): ?array
    {
        if ($this->snippetLines <= 0 || $line <= 0 || ! is_readable($file)) {
            return null;
        }

        $contents = @file($file, FILE_IGNORE_NEW_LINES);

        if ($contents === false) {
            return null;
        }

        $half = (int) floor($this->snippetLines / 2);
        $start = max(0, $line - $half - 1);
        $end = min(count($contents), $line + $half);

        $snippet = [];

        for ($i = $start; $i < $end; $i++) {
            // 1-based line numbers as keys so the UI can highlight $line.
            $snippet[$i + 1] = $contents[$i];
        }

        return $snippet;
    }
}
