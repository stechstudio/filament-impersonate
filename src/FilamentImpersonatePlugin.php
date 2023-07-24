<?php

namespace STS\FilamentImpersonate;

use Filament\Contracts\Plugin;
use Filament\Panel;
use STS\FilamentImpersonate\Middleware\ImpersonationBanner;

class FilamentImpersonatePlugin implements Plugin
{
    public function getId(): string
    {
        return 'filament-impersonate';
    }

    public function register(Panel $panel): void
    {
        $panel->middleware([
            ImpersonationBanner::class
        ]);
    }

    public function boot(Panel $panel): void
    {
        //
    }
}
