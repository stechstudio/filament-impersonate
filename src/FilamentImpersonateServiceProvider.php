<?php

namespace STS\FilamentImpersonate;

use Filament\PluginServiceProvider;

class FilamentImpersonateServiceProvider extends PluginServiceProvider
{
    public function boot()
    {
        $this->mergeConfigFrom(__DIR__.'/../config/filament-impersonate.php', 'filament-impersonate');

        $this->loadViewsFrom(__DIR__.'/../resources/views', 'impersonate');

        $this->loadRoutesFrom(__DIR__.'/../routes/web.php');
    }
}