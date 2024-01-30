<?php

namespace STS\FilamentImpersonate;

use Filament\Facades\Filament;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Event;
use Lab404\Impersonate\Events\LeaveImpersonation;
use Lab404\Impersonate\Events\TakeImpersonation;
use Spatie\LaravelPackageTools\Package;
use Filament\Support\Facades\FilamentView;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use STS\FilamentImpersonate\Tables\Actions\Impersonate;
use BladeUI\Icons\Factory;

class FilamentImpersonateServiceProvider extends PackageServiceProvider
{
    public static string $name = 'filament-impersonate';

    public function configurePackage(Package $package): void
    {
        $package
            ->name(static::$name)
            ->hasRoute('web')
            ->hasConfigFile()
            ->hasTranslations()
            ->hasViews();
    }

    public function registeringPackage(): void
    {
        Event::listen(TakeImpersonation::class, fn () => $this->clearAuthHashes());
        Event::listen(LeaveImpersonation::class, fn () => $this->clearAuthHashes());

        $this->registerIcon();
    }

    public function bootingPackage(): void
    {
        FilamentView::registerRenderHook(
            config('filament-impersonate.banner.render_hook', 'panels::body.start'),
            static fn (): string => Blade::render("<x-filament-impersonate::banner/>")
        );

        // For backwards compatibility we're going to load our views into the namespace we used to use as well.
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'impersonate');

        // Alias our table action for backwards compatibility.
        // STS\FilamentImpersonate\Impersonate is where that class used to exist, and I don't
        // want a breaking release yet.
        if (!class_exists(\STS\FilamentImpersonate\Impersonate::class)) {
            class_alias(Impersonate::class, \STS\FilamentImpersonate\Impersonate::class);
        }
    }

    protected function clearAuthHashes(): void
    {
        session()->forget(array_unique([
            'password_hash_' . session('impersonate.guard'),
            'password_hash_' . Filament::getCurrentPanel()->getAuthGuard(),
            'password_hash_' . Filament::getPanel(session()->get('impersonate.back_to_panel'))->getAuthGuard(),
            'password_hash_' . auth()->getDefaultDriver(),
            'password_hash_sanctum'
        ]));
    }

    protected function registerIcon(): void
    {
        $this->callAfterResolving(Factory::class, function (Factory $factory) {
            $factory->add('impersonate', [
                'path' => __DIR__.'/../resources/views/icons',
                'prefix' => 'impersonate',
            ]);
        });
    }
}
