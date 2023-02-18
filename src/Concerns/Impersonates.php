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

    protected function canBeImpersonated($target): bool
    {
        $current = Filament::auth()->user();

        return $current->isNot($target)
            && !app(ImpersonateManager::class)->isImpersonating()
            && (!method_exists($current, 'canImpersonate') || $current->canImpersonate())
            && (!method_exists($target, 'canBeImpersonated') || $target->canBeImpersonated());
    }

    public function impersonate($record): bool|Redirector|RedirectResponse
    {
        if (!$this->canBeImpersonated($record)) {
            return false;
        }

        session()->put([
            'impersonate.back_to' => request('fingerprint.path'),
            'impersonate.guard' => $this->getGuard()
        ]);

        app(ImpersonateManager::class)->take(
            Filament::auth()->user(),
            $record,
            $this->getGuard()
        );

        return redirect($this->getRedirectTo());
    }
}
