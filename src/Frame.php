<?php

declare(strict_types=1);

namespace DebugCat\Laravel;

/**
 * A single resolved stack frame, shaped exactly like a DebugCat
 * "stack_trace" entry.
 */
class Frame
{
    /**
     * @param  array<int, string>|null  $codeSnippet  map of line number => source line
     */
    public function __construct(
        public string $file,
        public int $line,
        public ?string $function = null,
        public ?string $class = null,
        public bool $inApp = false,
        public ?array $codeSnippet = null,
    ) {}

    /**
     * @return array{file: string, line: int, function: ?string, class: ?string, in_app: bool, code_snippet: ?array<int, string>}
     */
    public function toArray(): array
    {
        return [
            'file' => $this->file,
            'line' => $this->line,
            'function' => $this->function,
            'class' => $this->class,
            'in_app' => $this->inApp,
            'code_snippet' => $this->codeSnippet,
        ];
    }
}
