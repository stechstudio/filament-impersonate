<?php

namespace STS\FilamentImpersonate\Concerns;

use Closure;
use Filament\Facades\Filament;
use Illuminate\Http\RedirectResponse;
use Lab404\Impersonate\Services\ImpersonateManager;
use Livewire\Redirector;

trait Impersonates
{
    protected Closure|string|null $guard = null;

    protected Closure|string|null $redirectTo = null;

    public static function getDefaultName(): ?string
    {
        return 'impersonate';
    }

    public function guard(Closure|string $guard): self
    {
        $this->guard = $guard;

        return $this;
    }

    public function redirectTo(Closure|string $redirectTo): self
    {
        $this->redirectTo = $redirectTo;

        return $this;
    }

    public function getGuard(): string
    {
        return $this->evaluate($this->guard) ?? config('filament-impersonate.guard');
    }

    public function getRedirectTo(): string
    {
        return $this->evaluate($this->redirectTo) ?? config('filament-impersonate.redirect_to');
    }

    public function impersonate($record): bool|Redirector|RedirectResponse
    {
        if (!$this->allowed(Filament::auth()->user(), $record)) {
            return false;
        }

        app(ImpersonateManager::class)->take(
            Filament::auth()->user(),
            $record,
            $this->getGuard()
        );

        $this->clearPasswordHashes();

        session()->put('impersonate.back_to', request('fingerprint.path'));

        return redirect($this->getRedirectTo());
    }

    public function leave(): bool|Redirector|RedirectResponse
    {
        if(!app(ImpersonateManager::class)->isImpersonating()) {
            return redirect('/');
        }

        app(ImpersonateManager::class)->leave();

        $this->clearPasswordHashes();

        return redirect(
            session()->pull('impersonate.back_to') ?? config('filament.path')
        );
    }

    protected function clearPasswordHashes()
    {
        session()->forget(array_unique([
            'password_hash_' . $this->getGuard(),
            'password_hash_' . config('filament.auth.guard'),
            'password_hash_' . auth()->getDefaultDriver(),
        ]));
    }
}
