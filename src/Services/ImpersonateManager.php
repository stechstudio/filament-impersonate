<?php

namespace STS\FilamentImpersonate\Services;

use Illuminate\Contracts\Auth\Authenticatable;
use STS\FilamentImpersonate\Events\EnterImpersonation;
use STS\FilamentImpersonate\Events\LeaveImpersonation;
use STS\FilamentImpersonate\Guard\SessionGuard;

class ImpersonateManager
{
    const SESSION_KEY = 'impersonated_by';
    const SESSION_GUARD = 'impersonator_guard';
    const SESSION_GUARD_USING = 'impersonator_guard_using';
    const REMEMBER_PREFIX = 'remember_web';

    public function isImpersonating(): bool
    {
        return session()->has(static::SESSION_KEY);
    }

    public function getImpersonatorId(): int|string|null
    {
        return session(static::SESSION_KEY);
    }

    public function getImpersonator(): ?Authenticatable
    {
        $id = $this->getImpersonatorId();

        if (is_null($id)) {
            return null;
        }

        return $this->findUserByGuard($id, $this->getImpersonatorGuardName());
    }

    public function getImpersonatorGuardName(): ?string
    {
        return session(static::SESSION_GUARD);
    }

    public function getImpersonatorGuardUsingName(): ?string
    {
        return session(static::SESSION_GUARD_USING);
    }

    public function enter(Authenticatable $from, Authenticatable $to, ?string $guardName = null): bool
    {
        $this->saveAuthCookieInSession();

        try {
            $currentGuard = $this->getCurrentAuthGuardName();

            session()->put(static::SESSION_KEY, $from->getAuthIdentifier());
            session()->put(static::SESSION_GUARD, $currentGuard);
            session()->put(static::SESSION_GUARD_USING, $guardName);

            $this->guard($currentGuard)->quietLogout();
            $this->guard($guardName)->quietLogin($to);
        } catch (\Throwable) {
            $this->clear();

            return false;
        }

        event(new EnterImpersonation($from, $to));

        return true;
    }

    public function leave(): bool
    {
        try {
            $impersonated = auth()->guard($this->getImpersonatorGuardUsingName())->user();
            $impersonator = $this->findUserByGuard($this->getImpersonatorId(), $this->getImpersonatorGuardName());

            if (! $impersonator) {
                $this->clear();
                return false;
            }

            $this->guard($this->getCurrentAuthGuardName())->quietLogout();
            $this->guard($this->getImpersonatorGuardName())->quietLogin($impersonator);

            $this->extractAuthCookieFromSession();
            $this->clear();
        } catch (\Throwable) {
            $this->clear();

            return false;
        }

        event(new LeaveImpersonation($impersonator, $impersonated));

        return true;
    }

    public function clear(): void
    {
        session()->forget([
            static::SESSION_KEY,
            static::SESSION_GUARD,
            static::SESSION_GUARD_USING,
            static::REMEMBER_PREFIX,
        ]);
    }

    /**
     * Resolve the auth guard and ensure it's our custom SessionGuard.
     */
    protected function guard(?string $guardName): SessionGuard
    {
        $guard = auth()->guard($guardName);

        if (! $guard instanceof SessionGuard) {
            throw new \RuntimeException(
                "Impersonation requires a session-based auth guard. Guard [{$guardName}] is not a session guard."
            );
        }

        return $guard;
    }

    /**
     * Look up a user by ID using the auth provider configured for the given guard.
     * This handles multi-guard/multi-provider setups where different guards use different User models/tables.
     */
    protected function findUserByGuard(int|string $id, ?string $guardName): ?Authenticatable
    {
        if (empty($guardName)) {
            $guardName = config('auth.defaults.guard', 'web');
        }

        $providerName = config("auth.guards.{$guardName}.provider");

        if (empty($providerName)) {
            return null;
        }

        return auth()->createUserProvider($providerName)?->retrieveById($id);
    }

    /**
     * Determine which auth guard is currently authenticated.
     */
    protected function getCurrentAuthGuardName(): ?string
    {
        foreach (array_keys(config('auth.guards')) as $guard) {
            if (auth()->guard($guard)->check()) {
                return $guard;
            }
        }

        return null;
    }

    /**
     * Save any remember-me cookies in the session before switching users.
     */
    protected function saveAuthCookieInSession(): void
    {
        $cookies = collect(request()->cookies->all())
            ->filter(fn ($val, $key) => str_starts_with($key, static::REMEMBER_PREFIX));

        $key = $cookies->keys()->first();
        $val = $cookies->values()->first();

        if (! $key || ! $val) {
            return;
        }

        session()->put(static::REMEMBER_PREFIX, [$key, $val]);
    }

    /**
     * Restore remember-me cookies from the session after leaving impersonation.
     */
    protected function extractAuthCookieFromSession(): void
    {
        $saved = session(static::REMEMBER_PREFIX);

        if (! $saved || ! is_array($saved) || count($saved) !== 2) {
            return;
        }

        cookie()->queue($saved[0], $saved[1]);
    }
}
