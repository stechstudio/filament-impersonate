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
        $target = $this->getQuery()->find($key);
        $this->authorizeImpersonate($target);

        app(ImpersonateManager::class)->take(
            auth()->user(), $target, isset($this->guard) ? $this->guard : config('filament-impersonate.default_guard')
        );

        session()->put('impersonate.back_to', request('fingerprint.path'));

        return redirect(isset($this->redirect) ? $this->redirect : config('filament-impersonate.redirect_to'));
    }

    protected function authorizeImpersonate($target)
    {
        if(!Impersonate::allowed(auth()->user(), $target)) {
            abort(403);
        }
    }
}