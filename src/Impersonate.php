<?php

namespace STS\FilamentImpersonate;

use Filament\Tables\Actions\IconButtonAction;
use Lab404\Impersonate\Services\ImpersonateManager;

class Impersonate extends IconButtonAction
{
    protected function setUp(): void
    {
        $this
            ->icon('impersonate::icon')
            ->action(fn($record) => static::impersonate($record))
            ->hidden(fn ($record) => !static::allowed(auth()->user(), $record));
    }

    protected static function allowed($current, $target)
    {
        return $current->isNot($target)
            && !app(ImpersonateManager::class)->isImpersonating()
            && (!method_exists($current, 'canImpersonate') || $current->canImpersonate())
            && (!method_exists($target, 'canBeImpersonated') || $target->canBeImpersonated());
    }

    protected static function impersonate($record)
    {
        if(!static::allowed(auth()->user(), $record)) {
            return false;
        }

        app(ImpersonateManager::class)->take(
            auth()->user(), $record, config('filament-impersonate.guard')
        );

        session()->forget('password_hash_' . config('filament-impersonate.guard'));
        session()->forget('password_hash_' . config('filament.auth.guard'));

        session()->put('impersonate.back_to', request('fingerprint.path'));

        return redirect(config('filament-impersonate.redirect_to'));
    }
}
