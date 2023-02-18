<?php

namespace STS\FilamentImpersonate;

use Filament\Facades\Filament;
use Illuminate\Contracts\Auth\Authenticatable;
use Lab404\Impersonate\Services\ImpersonateManager;

class FilamentImpersonateManager extends ImpersonateManager
{
    public function canBeImpersonated(Authenticatable $target): bool
    {
        $current = Filament::auth()->user();

        return $current->isNot($target)
            && !app(ImpersonateManager::class)->isImpersonating()
            && (!method_exists($current, 'canImpersonate') || $current->canImpersonate())
            && (!method_exists($target, 'canBeImpersonated') || $target->canBeImpersonated());
    }
}