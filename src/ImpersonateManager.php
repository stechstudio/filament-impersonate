<?php

namespace STS\FilamentImpersonate;

use Illuminate\Auth\SessionGuard;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Log;
use STS\FilamentImpersonate\Events\EnterImpersonation;
use STS\FilamentImpersonate\Events\LeaveImpersonation;

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

            // Resolve both guards and capture session keys before manipulating state
            $fromGuard = $this->resolveSessionGuard($currentGuard);
            $toGuard = $this->resolveSessionGuard($guardName);

            session()->put(static::SESSION_KEY, $from->getAuthIdentifier());
            session()->put(static::SESSION_GUARD, $currentGuard);
            session()->put(static::SESSION_GUARD_USING, $guardName);

            // Clear current user from session (no events fired)
            session()->forget($fromGuard->getName());
            session()->forget($fromGuard->getRecallerName());

            // Set target user in session (no events fired, no session ID migration)
            session()->put($toGuard->getName(), $to->getAuthIdentifier());

            // Force auth to re-read from session
            auth()->forgetGuards();
        } catch (\Throwable $e) {
            Log::warning('Impersonation enter() failed: '.$e->getMessage(), [
                'guard' => $guardName,
                'from' => $from->getAuthIdentifier(),
                'to' => $to->getAuthIdentifier(),
            ]);

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

            // Resolve both guards and capture session keys before manipulating state
            $currentGuard = $this->resolveSessionGuard($this->getCurrentAuthGuardName());
            $impersonatorGuard = $this->resolveSessionGuard($this->getImpersonatorGuardName());

            // Clear impersonated user from session
            session()->forget($currentGuard->getName());
            session()->forget($currentGuard->getRecallerName());

            // Restore impersonator in session
            session()->put($impersonatorGuard->getName(), $impersonator->getAuthIdentifier());

            // Force auth to re-read from session
            auth()->forgetGuards();

            $this->extractAuthCookieFromSession();
            $this->clear();
        } catch (\Throwable $e) {
            Log::warning('Impersonation leave() failed: '.$e->getMessage());

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

    protected function resolveSessionGuard(?string $guardName): SessionGuard
    {
        $guard = auth()->guard($guardName);

        if (! $guard instanceof SessionGuard) {
            throw new \RuntimeException(
                "Impersonation requires a session-based auth guard. Guard [{$guardName}] is not a session guard."
            );
        }

        return $guard;
    }

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

    protected function getCurrentAuthGuardName(): ?string
    {
        foreach (array_keys(config('auth.guards')) as $guard) {
            if (auth()->guard($guard)->check()) {
                return $guard;
            }
        }

        return null;
    }

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

    protected function extractAuthCookieFromSession(): void
    {
        $saved = session(static::REMEMBER_PREFIX);

        if (! $saved || ! is_array($saved) || count($saved) !== 2) {
            return;
        }

        cookie()->queue($saved[0], $saved[1]);
    }
}
