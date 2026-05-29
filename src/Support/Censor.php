<?php

declare(strict_types=1);

namespace DebugCat\Laravel\Support;

/**
 * Recursively redacts sensitive values by key name before a payload leaves
 * the application.
 */
class Censor
{
    /**
     * @param  list<string>  $fields  case-insensitive key names to redact
     */
    public function __construct(
        protected array $fields = [],
        protected string $replacement = '[CENSORED]',
    ) {
        $this->fields = array_map('strtolower', $this->fields);
    }

    /**
     * @param  array<array-key, mixed>  $data
     * @return array<array-key, mixed>
     */
    public function scrub(array $data): array
    {
        foreach ($data as $key => $value) {
            if (is_string($key) && in_array(strtolower($key), $this->fields, true)) {
                $data[$key] = $this->replacement;

                continue;
            }

            if (is_array($value)) {
                $data[$key] = $this->scrub($value);
            }
        }

        return $data;
    }
}
