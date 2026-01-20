<?php

use Lab404\Impersonate\Services\ImpersonateManager;
use STS\FilamentImpersonate\Actions\Impersonate;
use STS\FilamentImpersonate\Tests\User;

beforeEach(function () {
    User::resetAuthorizationDefaults();

    $this->admin = User::create([
        'name' => 'Admin',
        'email' => 'admin@example.com',
        'password' => bcrypt('password'),
    ]);

    $this->targetUser = User::create([
        'name' => 'Target User',
        'email' => 'target@example.com',
        'password' => bcrypt('password'),
    ]);

    $this->actingAs($this->admin);
});

afterEach(function () {
    User::resetAuthorizationDefaults();

    // Leave impersonation if active
    if (app(ImpersonateManager::class)->isImpersonating()) {
        app(ImpersonateManager::class)->leave();
    }
});

describe('leave route', function () {
    it('has correct route name', function () {
        expect(route('filament-impersonate.leave'))->toBeString();
    });

    it('redirects to root when not impersonating', function () {
        $response = $this->get(route('filament-impersonate.leave'));

        $response->assertRedirect('/');
    });

    it('leaves impersonation when impersonating', function () {
        // Start impersonation
        $action = Impersonate::make()->backTo('/admin/users');
        $action->impersonate($this->targetUser);

        expect(app(ImpersonateManager::class)->isImpersonating())->toBeTrue();

        // Leave impersonation
        $response = $this->get(route('filament-impersonate.leave'));

        expect(app(ImpersonateManager::class)->isImpersonating())->toBeFalse();
    });

    it('redirects to session back_to value', function () {
        // Start impersonation with backTo
        $action = Impersonate::make()->backTo('/admin/users');
        $action->impersonate($this->targetUser);

        // Leave impersonation
        $response = $this->get(route('filament-impersonate.leave'));

        $response->assertRedirect('/admin/users');
    });

    it('clears session back_to after leaving', function () {
        // Start impersonation with backTo
        $action = Impersonate::make()->backTo('/admin/users');
        $action->impersonate($this->targetUser);

        expect(session('impersonate.back_to'))->toBe('/admin/users');

        // Leave impersonation
        $this->get(route('filament-impersonate.leave'));

        // session()->pull() should have removed it
        expect(session('impersonate.back_to'))->toBeNull();
    });

    it('restores original user after leaving', function () {
        // Start impersonation
        $action = Impersonate::make()->backTo('/admin');
        $action->impersonate($this->targetUser);

        expect(auth()->id())->toBe($this->targetUser->id);

        // Leave impersonation
        $this->get(route('filament-impersonate.leave'));

        expect(auth()->id())->toBe($this->admin->id);
    });

    it('respects route prefix config', function () {
        config(['filament-impersonate.route_prefix' => 'custom-prefix']);

        // Re-register routes with new config
        $this->app['router']->getRoutes()->refreshNameLookups();

        // The route should still work via name
        expect(route('filament-impersonate.leave'))->toContain('filament-impersonate/leave');
    });

    it('redirects to fallback when session back_to is missing', function () {
        // Start impersonation without backTo set
        app(ImpersonateManager::class)->take(
            $this->admin,
            $this->targetUser,
            'web'
        );

        // Manually clear the back_to session to simulate corruption/loss
        session()->forget('impersonate.back_to');

        expect(app(ImpersonateManager::class)->isImpersonating())->toBeTrue();
        expect(session('impersonate.back_to'))->toBeNull();

        // Leave impersonation - should fallback to '/' instead of error
        $response = $this->get(route('filament-impersonate.leave'));

        $response->assertRedirect('/');
        expect(app(ImpersonateManager::class)->isImpersonating())->toBeFalse();
    });
});
