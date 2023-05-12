<?php

namespace STS\FilamentImpersonate;

use Closure;
use Filament\Facades\Filament;
use Filament\Tables\Actions\Action;
use Illuminate\Http\RedirectResponse;
use Lab404\Impersonate\Services\ImpersonateManager;
use Livewire\Redirector;

class Impersonate extends Action
{
    protected Closure|string|null $guard = null;

    protected Closure|string|null $redirectTo = null;

    public static function getDefaultName(): ?string
    {
        return 'impersonate';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->iconButton()
            ->icon('impersonate-icon')
            ->action(fn ($record) => $this->impersonate($record))
            ->hidden(fn ($record) => !static::allowed(Filament::auth()->user(), $record));
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

    protected static function allowed($current, $target): bool
    {
        return $current->isNot($target)
            && !app(ImpersonateManager::class)->isImpersonating()
            && (!method_exists($current, 'canImpersonate') || $current->canImpersonate())
            && (!method_exists($target, 'canBeImpersonated') || $target->canBeImpersonated());
    }

    public function impersonate($record): bool|Redirector|RedirectResponse
    {
        if (!static::allowed(Filament::auth()->user(), $record)) {
            return false;
        }

        app(ImpersonateManager::class)->take(
            Filament::auth()->user(),
            $record,
            $this->getGuard()
        );

        session()->forget(array_unique([
            'password_hash_' . config('filament-impersonate.guard'),
            'password_hash_' . Filament::getCurrentContext()->getAuthGuard()
        ]));
        session()->put('impersonate.back_to', request('fingerprint.path'));
        session()->put('impersonate.back_to_context', Filament::getCurrentContext()->getId());

        return redirect($this->getRedirectTo());
    }

    public static function leave(): bool|Redirector|RedirectResponse
    {
        if(!app(ImpersonateManager::class)->isImpersonating()) {
            return redirect('/');
        }

        app(ImpersonateManager::class)->leave();

        $context = Filament::getContext(session()->get('impersonate.back_to_context'));

        session()->forget(array_unique([
            'password_hash_' . config('filament-impersonate.guard'),
            'password_hash_' . Filament::getCurrentContext()->getAuthGuard(),
            'password_hash_' . $context->getAuthGuard(),
        ]));

        return redirect(
            session()->pull('impersonate.back_to') ?? $context->getUrl()
        );
    }
}
