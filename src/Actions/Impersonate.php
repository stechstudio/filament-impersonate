<?php

namespace STS\FilamentImpersonate\Actions;

use Closure;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\RedirectResponse;
use Lab404\Impersonate\Services\ImpersonateManager;

class Impersonate extends Action
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->label(__('filament-impersonate::action.label'));
        $this->icon('impersonate-icon');

        $this->impersonateRecord(fn($record) => $record);
        $this->action(fn() => $this->impersonate($this->evaluate($this->impersonateRecord)));

        // Note: Not entirely sure why, but if we don't pass the record as a named parameter, this evaluate call doesn't
        // automatically resolve it. The end result is that ->impersonateRecord(fn ($record) => $record->whateverRelationship)
        // doesn't work in a table because `record` is null and thus the action invisible.
        $this->visible(fn($record) => $this->canImpersonate($this->evaluate($this->impersonateRecord, ['record' => $record])));
    }

    protected Closure|string|null $guard = null;

    protected Closure|string|null $redirectTo = null;

    protected Closure|string|null $backTo = null;

    protected Authenticatable|Closure|null $impersonateRecord = null;

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

    public function backTo(Closure|string $backTo): self
    {
        $this->backTo = $backTo;

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

    protected function canImpersonate($target): bool
    {
        $current = Filament::auth()->user();

        return filled($target)
            && $current->isNot($target)
            && !app(ImpersonateManager::class)->isImpersonating()
            && (config('filament-impersonate.allow_soft_deleted') || !method_exists($target, 'bootSoftDeletes') || !$target->trashed())
            && (!method_exists($current, 'canImpersonate') || $current->canImpersonate())
            && (!method_exists($target, 'canBeImpersonated') || $target->canBeImpersonated());
    }

    public function impersonate($record): bool|RedirectResponse
    {
        if (!$this->canImpersonate($record)) {
            return false;
        }

        session()->put([
            'impersonate.back_to' => $this->getBackTo() ?? request('fingerprint.path', request()->header('referer')) ?? Filament::getCurrentOrDefaultPanel()->getUrl(),
            'impersonate.guard' => $this->getGuard()
        ]);

        app(ImpersonateManager::class)->take(
            Filament::auth()->user(),
            $record,
            $this->getGuard()
        );

        $redirectTo = $this->getRedirectTo();

        // Use Livewire redirect when available (e.g., in modals), otherwise fall back to standard redirect
        if ($this->getLivewire()) {
            $this->redirect($redirectTo);
            return true;
        }

        return redirect($redirectTo);
    }

    public function impersonateRecord(Authenticatable|Closure|null $record, Closure|bool|null $visible = null): static
    {
        $this->impersonateRecord = $record;

        return $this;
    }
}
