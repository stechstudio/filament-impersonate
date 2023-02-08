<?php

namespace STS\FilamentImpersonate;

use Filament\Facades\Filament;
use Filament\PluginServiceProvider;
use Illuminate\Support\Facades\Blade;

class FilamentImpersonateServiceProvider extends PluginServiceProvider
{
    public static string $name = 'filament-impersonate';

    public function boot()
    {
        $this->mergeConfigFrom(__DIR__.'/../config/filament-impersonate.php', 'filament-impersonate');

        $this->loadViewsFrom(__DIR__.'/../resources/views', 'filament-impersonate');

        $this->loadRoutesFrom(__DIR__.'/../routes/web.php');
    }

    public function packageBooted(): void
    {
        Filament::registerRenderHook(
            'body.start',
            static fn (): string => Blade::render("<x-filament-impersonate::banner/>")
        );
    }
}
