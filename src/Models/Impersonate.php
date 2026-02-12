<?php

namespace STS\FilamentImpersonate\Models;

use Illuminate\Database\Eloquent\Model;
use STS\FilamentImpersonate\Services\ImpersonateManager;

/**
 * Trait to add impersonation capabilities to User models.
 *
 * Add this trait to your User model and optionally override
 * canImpersonate() and canBeImpersonated() to control access.
 */
trait Impersonate
{
    public function canImpersonate(): bool
    {
        return true;
    }

    public function canBeImpersonated(): bool
    {
        return true;
    }

    public function impersonate(Model $user, ?string $guardName = null): bool
    {
        return app(ImpersonateManager::class)->take($this, $user, $guardName);
    }

    public function isImpersonated(): bool
    {
        return app(ImpersonateManager::class)->isImpersonating();
    }

    public function leaveImpersonation(): bool
    {
        return app(ImpersonateManager::class)->leave();
    }
}
