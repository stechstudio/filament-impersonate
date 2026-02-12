<?php

namespace STS\FilamentImpersonate;

use BladeUI\Icons\Factory;
use Filament\Facades\Filament;
use Filament\Support\Facades\FilamentView;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Event;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use STS\FilamentImpersonate\Facades\Impersonation;
use STS\FilamentImpersonate\Events\EnterImpersonation;
use STS\FilamentImpersonate\Events\LeaveImpersonation;
use STS\FilamentImpersonate\Guard\SessionGuard;
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
        Event::listen(Login::class, fn () => Impersonation::clear());
        Event::listen(Logout::class, fn () => Impersonation::clear());

        $this->registerIcon();
    }

    public function bootingPackage(): void
    {
        $this->registerSessionGuard();

        FilamentView::registerRenderHook(
            config('filament-impersonate.banner.render_hook', 'panels::body.start'),
            static fn (): string => Blade::render('<x-filament-impersonate::banner/>')
        );

        $this->loadViewsFrom(__DIR__.'/../resources/views', 'impersonate');
    }

    protected function registerSessionGuard(): void
    {
        Auth::extend('session', function ($app, string $name, array $config) {
            $guard = new SessionGuard(
                $name,
                Auth::createUserProvider($config['provider'] ?? null),
                $app['session.store'],
            );

            $guard->setCookieJar($app['cookie']);
            $guard->setDispatcher($app['events']);
            $guard->setRequest($app->refresh('request', $guard, 'setRequest'));

            if (isset($config['remember'])) {
                $guard->setRememberDuration($config['remember']);
            }

            return $guard;
        });
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

            if ($panelId = session('impersonate.back_to_panel')) {
                $guards->push(Filament::getPanel($panelId)?->getAuthGuard());
            }
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
