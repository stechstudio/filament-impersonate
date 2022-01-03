<?php

namespace STS\FilamentImpersonate;

use Filament\PluginServiceProvider;
use STS\FilamentImpersonate\Middleware\ImpersonationBanner;

class FilamentImpersonateServiceProvider extends PluginServiceProvider
{
    public static string $name = 'filament-impersonate';

    public function register()
    {
        $this->app['config']->push('filament.middleware.base', ImpersonationBanner::class);
    }

    public function boot()
    {
        $this->mergeConfigFrom(__DIR__.'/../config/filament-impersonate.php', 'filament-impersonate');

        $this->loadViewsFrom(__DIR__.'/../resources/views', 'impersonate');

        $this->loadRoutesFrom(__DIR__.'/../routes/web.php');
    }
}
