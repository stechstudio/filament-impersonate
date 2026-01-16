<?php

namespace STS\FilamentImpersonate\Tests;

use Filament\Facades\Filament;
use Filament\FilamentServiceProvider;
use Filament\Http\Middleware\Authenticate;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\SupportServiceProvider;
use Filament\Actions\ActionsServiceProvider;
use Illuminate\Database\Schema\Blueprint;
use Livewire\LivewireServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;
use STS\FilamentImpersonate\FilamentImpersonateServiceProvider;
use Lab404\Impersonate\ImpersonateServiceProvider;
use BladeUI\Icons\BladeIconsServiceProvider;
use BladeUI\Heroicons\BladeHeroiconsServiceProvider;

class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpDatabase();
        $this->setUpFilamentPanel();
    }

    protected function getPackageProviders($app): array
    {
        return [
            LivewireServiceProvider::class,
            BladeIconsServiceProvider::class,
            BladeHeroiconsServiceProvider::class,
            SupportServiceProvider::class,
            ActionsServiceProvider::class,
            FilamentServiceProvider::class,
            ImpersonateServiceProvider::class,
            FilamentImpersonateServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
        ]);

        $app['config']->set('filament-impersonate.redirect_to', '/default-redirect');
        $app['config']->set('auth.providers.users.model', User::class);
        $app['config']->set('app.key', 'base64:'.base64_encode(random_bytes(32)));
    }

    protected function setUpFilamentPanel(): void
    {
        $panel = Panel::make()
            ->default()
            ->id('admin')
            ->path('admin')
            ->authGuard('web');

        Filament::registerPanel($panel);
        Filament::setCurrentPanel($panel);
    }

    protected function setUpDatabase(): void
    {
        $this->app['db']->connection()->getSchemaBuilder()->create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->timestamps();
            $table->softDeletes();
        });

        $this->app['db']->connection()->getSchemaBuilder()->create('providers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->foreignId('user_id')->constrained();
            $table->timestamps();
        });
    }
}
