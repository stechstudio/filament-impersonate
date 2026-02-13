<?php

namespace STS\FilamentImpersonate\Actions;

use Closure;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\RedirectResponse;
use STS\FilamentImpersonate\Facades\Impersonation;

class Impersonate extends Action
{
    protected Closure|string|null $guard = null;

    protected Closure|string|null $redirectTo = null;

    protected Closure|string|null $backTo = null;

    protected Authenticatable|Closure|null $impersonateRecord = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->label(__('filament-impersonate::action.label'));
        $this->icon('impersonate-icon');

        $this->impersonateRecord(fn ($record) => $record);
        // Filament's evaluate() only auto-injects closure parameters that match named bindings.
        // We pass 'record' explicitly so that closures like fn($record) => $record->relationship
        // resolve correctly in table row contexts where the record isn't otherwise bound.
        $this->action(fn ($record) => $this->impersonate($this->evaluate($this->impersonateRecord, ['record' => $record])));
        $this->visible(fn ($record) => $this->canImpersonate($this->evaluate($this->impersonateRecord, ['record' => $record])));
    }

    public static function getDefaultName(): ?string
    {
        return 'impersonate';
    }

    public function guard(Closure|string $guard): static
    {
        $this->guard = $guard;

        return $this;
    }

    public function redirectTo(Closure|string $redirectTo): static
    {
        $this->redirectTo = $redirectTo;

        return $this;
    }

    public function backTo(Closure|string $backTo): static
    {
        $this->backTo = $backTo;

        return $this;
    }

    public function impersonateRecord(Authenticatable|Closure|null $record): static
    {
        $this->impersonateRecord = $record;

        return $this;
    }

    public function getGuard(): string
    {
        return $this->evaluate($this->guard) ?? Filament::getCurrentOrDefaultPanel()->getAuthGuard();
    }

    public function getRedirectTo(): string
    {
        return $this->evaluate($this->redirectTo) ?? config('filament-impersonate.redirect_to');
    }

    public function getBackTo(): ?string
    {
        return $this->evaluate($this->backTo);
    }

    public function impersonate($record): bool|RedirectResponse
    {
        if (! $this->canImpersonate($record)) {
            return false;
        }

        $guard = $this->getGuard();

        session()->put([
            'impersonate.back_to' => $this->resolveBackToUrl(),
            'impersonate.guard' => $guard,
        ]);

        if (! Impersonation::enter(Filament::auth()->user(), $record, $guard)) {
            Notification::make()
                ->title(__('filament-impersonate::action.failed'))
                ->danger()
                ->send();

            return false;
        }

        $redirectTo = $this->getRedirectTo();

        if ($this->getLivewire()) {
            $this->redirect($redirectTo);

            return true;
        }

        return redirect($redirectTo);
    }

    protected function canImpersonate($target): bool
    {
        $current = Filament::auth()->user();

        if (is_null($current) || blank($target) || $current->is($target)) {
            return false;
        }

        if (Impersonation::isImpersonating()) {
            return false;
        }

        if ($this->isSoftDeleted($target) && ! config('filament-impersonate.allow_soft_deleted')) {
            return false;
        }

        if (method_exists($current, 'canImpersonate') && ! $current->canImpersonate()) {
            return false;
        }

        if (method_exists($target, 'canBeImpersonated') && ! $target->canBeImpersonated()) {
            return false;
        }

        return true;
    }

    protected function isSoftDeleted($record): bool
    {
        return method_exists($record, 'trashed') && $record->trashed();
    }

    protected function resolveBackToUrl(): string
    {
        return $this->getBackTo()
            ?? request('fingerprint.path', request()->header('referer'))
            ?? Filament::getCurrentOrDefaultPanel()->getUrl();
    }
}
