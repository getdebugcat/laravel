<?php

declare(strict_types=1);

namespace DebugCat\Laravel\Context;

use DebugCat\Laravel\Report;
use Illuminate\Http\Request;

/**
 * Attaches the authenticated user (id, email, name) to the occurrence so you
 * can see who hit the error. The user is resolved lazily at send-time so it
 * reflects the authentication state when the exception occurred.
 */
class UserContextProvider implements ContextProvider
{
    public function __construct(
        protected ?Request $request,
    ) {}

    public function enrich(Report $report): void
    {
        $user = $this->request?->user();

        if ($user === null) {
            return;
        }

        $attributes = array_filter([
            'id' => $user->getAuthIdentifier(),
            'email' => $this->stringAttribute($user, 'email'),
            'name' => $this->stringAttribute($user, 'name'),
        ], fn ($value) => $value !== null);

        if ($attributes !== []) {
            $report->setUser($attributes);
        }
    }

    protected function stringAttribute(object $user, string $key): ?string
    {
        $value = data_get($user, $key);

        return is_scalar($value) ? (string) $value : null;
    }
}
