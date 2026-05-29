<?php

declare(strict_types=1);

namespace DebugCat\Laravel;

use DateTimeInterface;
use DebugCat\Laravel\Support\Backtrace;
use Throwable;

/**
 * A captured exception, modelled on the DebugCat ingest contract. Its
 * {@see toArray()} output is exactly the JSON body POSTed to /api/ingest.
 */
class Report
{
    /** @var list<Frame> */
    protected array $frames = [];

    /** @var array<string, mixed> */
    protected array $context = [];

    /** @var array<string, mixed>|null */
    protected ?array $user = null;

    public function __construct(
        public string $exceptionClass,
        public ?string $message = null,
        public string $level = 'error',
        public ?DateTimeInterface $occurredAt = null,
    ) {}

    public static function fromThrowable(Throwable $throwable, Backtrace $backtrace, string $level = 'error'): self
    {
        $report = new self(
            exceptionClass: $throwable::class,
            message: $throwable->getMessage(),
            level: $level,
        );

        $report->frames = $backtrace->fromThrowable($throwable);

        return $report;
    }

    /**
     * @param  array<string, mixed>  $context
     */
    public function mergeContext(array $context): self
    {
        $this->context = array_merge($this->context, $context);

        return $this;
    }

    /**
     * @param  array<string, mixed>  $user
     */
    public function setUser(array $user): self
    {
        $this->user = $user;

        return $this;
    }

    public function setLevel(string $level): self
    {
        $this->level = $level;

        return $this;
    }

    /**
     * @return array{
     *     exception_class: string,
     *     message: ?string,
     *     level: string,
     *     stack_trace: list<array<string, mixed>>,
     *     context: array<string, mixed>|null,
     *     user: array<string, mixed>|null,
     *     occurred_at: string
     * }
     */
    public function toArray(): array
    {
        return [
            'exception_class' => $this->exceptionClass,
            'message' => $this->message,
            'level' => $this->level,
            'stack_trace' => array_map(fn (Frame $frame) => $frame->toArray(), $this->frames),
            'context' => $this->context === [] ? null : $this->context,
            'user' => $this->user,
            'occurred_at' => ($this->occurredAt ?? new \DateTimeImmutable)->format(DateTimeInterface::ATOM),
        ];
    }
}
