<?php

namespace STS\FilamentImpersonate;

use Filament\PluginServiceProvider;
use Illuminate\Support\Facades\Event;
use Lab404\Impersonate\Events\LeaveImpersonation;
use Lab404\Impersonate\Events\TakeImpersonation;
use Spatie\LaravelPackageTools\Package;
use STS\FilamentImpersonate\Tables\Actions\Impersonate;

class FilamentImpersonateServiceProvider extends PluginServiceProvider
{
    public static string $name = 'filament-impersonate';

    public function registeringPackage()
    {
        Event::listen(TakeImpersonation::class, fn() => $this->clearAuthHashes());
        Event::listen(LeaveImpersonation::class, fn() => $this->clearAuthHashes());
    }

    public function packageConfiguring(Package $package): void
    {
        $package->hasRoute('web');
    }

    public function bootingPackage()
    {
        // For backwards compatibility we're going to load our views into the namespace we used to use as well.
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'impersonate');

        // Alias our table action for backwards compatibility.
        // STS\FilamentImpersonate\Impersonate is where that class used to exist, and I don't
        // want a breaking release yet.
        if(!class_exists('STS\\FilamentImpersonate\\Impersonate')) {
            class_alias(Impersonate::class, 'STS\\FilamentImpersonate\\Impersonate');
        }
    }

    protected function clearAuthHashes()
    {
        session()->forget(array_unique([
            'password_hash_' . session('impersonate.guard'),
            'password_hash_' . config('filament.auth.guard'),
            'password_hash_' . auth()->getDefaultDriver(),
            'password_hash_sanctum'
        ]));
    }
}
