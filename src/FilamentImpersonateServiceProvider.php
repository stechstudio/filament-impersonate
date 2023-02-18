<?php

namespace STS\FilamentImpersonate;

use Filament\PluginServiceProvider;
use STS\FilamentImpersonate\Middleware\ImpersonationBanner;
use STS\FilamentImpersonate\Tables\Actions\Impersonate;

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

        $this->loadTranslationsFrom(__DIR__.'/../lang', 'filament-impersonate');

        // Alias our table action for backwards compatibility.
        // STS\FilamentImpersonate\Impersonate is where that class used to exist, and I don't
        // want a breaking release yet.
        class_alias(Impersonate::class, 'STS\\FilamentImpersonate\\Impersonate');
    }
}
