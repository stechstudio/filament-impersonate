<?php

namespace STS\FilamentImpersonate;

use BladeUI\Icons\Factory;
use Filament\Facades\Filament;
use Filament\Support\Facades\FilamentView;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Event;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use STS\FilamentImpersonate\Facades\Impersonation;
use STS\FilamentImpersonate\Events\EnterImpersonation;
use STS\FilamentImpersonate\Events\LeaveImpersonation;
use STS\FilamentImpersonate\ImpersonateManager;

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
        $this->app->scoped(ImpersonateManager::class);
        $this->app->alias(ImpersonateManager::class, 'impersonate');

        Event::listen(EnterImpersonation::class, fn () => $this->clearAuthHashes());
        Event::listen(LeaveImpersonation::class, fn () => $this->clearAuthHashes());
        Event::listen(Login::class, fn (Login $event) => $this->clearImpersonationForGuard($event->guard));
        Event::listen(Logout::class, fn (Logout $event) => $this->clearImpersonationForGuard($event->guard));

        $this->registerIcon();
    }

    public function bootingPackage(): void
    {
        FilamentView::registerRenderHook(
            config('filament-impersonate.banner.render_hook', 'panels::body.start'),
            static fn (): string => Blade::render('<x-filament-impersonate::banner/>')
        );

        $this->loadViewsFrom(__DIR__.'/../resources/views', 'impersonate');
    }

    /**
     * Clear impersonation in response to an auth event, but only when the event
     * belongs to a guard involved in the active impersonation.
     *
     * Apps that share a single session across multiple guards (e.g. an admin
     * guard alongside a separate customer/storefront guard) would otherwise have
     * an active impersonation silently torn down when an unrelated guard fires
     * Login/Logout — a customer logging in on the storefront, say. The
     * impersonator's own session key is untouched, so they remain authenticated
     * as the impersonated user but isImpersonating() returns false, leaving them
     * with no banner and no way to leave. Only the impersonator's guard chain may
     * end the impersonation.
     */
    protected function clearImpersonationForGuard(?string $guard): void
    {
        if (Impersonation::isImpersonating()) {
            $impersonationGuards = array_filter([
                Impersonation::getImpersonatorGuardName(),
                Impersonation::getImpersonatorGuardUsingName(),
            ]);

            if ($guard !== null && $impersonationGuards !== [] && ! in_array($guard, $impersonationGuards, true)) {
                return;
            }
        }

        Impersonation::clear();
    }

    protected function clearAuthHashes(): void
    {
        $guards = collect([
            'sanctum',
            auth()->getDefaultDriver(),
            session('impersonate.guard'),
        ]);

        try {
            $guards->push(Filament::getCurrentOrDefaultPanel()?->getAuthGuard());
        } catch (\Throwable) {
            //
        }

        session()->forget(
            $guards->filter()->unique()->map(fn (string $guard) => "password_hash_{$guard}")->all()
        );
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
