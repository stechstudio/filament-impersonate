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
    }

    protected function clearAuthHashes(): void
    {
        $hashes = [
            'password_hash_sanctum',
            'password_hash_' . auth()->getDefaultDriver(),
        ];

        if ($guard = session('impersonate.guard')) {
            $hashes[] = 'password_hash_' . $guard;
        }

        try {
            if ($panel = Filament::getCurrentOrDefaultPanel()) {
                $hashes[] = 'password_hash_' . $panel->getAuthGuard();
            }

            if ($backToPanelId = session()->get('impersonate.back_to_panel')) {
                if ($panel = Filament::getPanel($backToPanelId)) {
                    $hashes[] = 'password_hash_' . $panel->getAuthGuard();
                }
            }
        } catch (\Throwable $e) {
            // Log or handle the error if needed
        }

        session()->forget(array_unique($hashes));
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
