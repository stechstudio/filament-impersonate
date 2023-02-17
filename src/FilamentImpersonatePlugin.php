<?php

namespace STS\FilamentImpersonate;

use Filament\Context;
use Filament\Contracts\Plugin;
use STS\FilamentImpersonate\Middleware\ImpersonationBanner;

class FilamentImpersonatePlugin implements Plugin
{
    public function getId(): string
    {
        return 'filament-impersonate';
    }

    public function register(Context $context): void
    {
        $context->middleware([
            ImpersonationBanner::class
        ]);
    }

    public function boot(Context $context): void
    {
        //
    }
}
