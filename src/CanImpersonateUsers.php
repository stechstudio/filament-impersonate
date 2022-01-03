<?php

namespace STS\FilamentImpersonate;

use Filament\Resources\Pages\ListRecords;
use Lab404\Impersonate\Services\ImpersonateManager;

/**
 * @mixin ListRecords
 */
trait CanImpersonateUsers
{
    public function impersonate($key)
    {
        $target = $this->getTableQuery()->find($key);
        $this->authorizeImpersonate($target);

        app(ImpersonateManager::class)->take(
            auth()->user(), $target, config('filament-impersonate.guard')
        );

        session()->forget('password_hash_' . config('filament-impersonate.guard'));
        session()->put('impersonate.back_to', request('fingerprint.path'));

        return redirect(isset($this->url) ? $this->url : config('filament-impersonate.redirect_to'));
    }

    protected function authorizeImpersonate($target)
    {
        if(!Impersonate::allowed(auth()->user(), $target)) {
            abort(403);
        }
    }
}
