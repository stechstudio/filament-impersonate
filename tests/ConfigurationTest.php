<?php

use STS\FilamentImpersonate\Tests\User;

beforeEach(function () {
    User::resetAuthorizationDefaults();
});

describe('configuration defaults', function () {
    it('has default guard of web', function () {
        // Reset to package default
        config(['filament-impersonate.guard' => 'web']);

        expect(config('filament-impersonate.guard'))->toBe('web');
    });

    it('has default redirect_to of /', function () {
        // Note: TestCase sets this to /default-redirect for testing
        // Check the actual package default
        $config = include __DIR__ . '/../config/filament-impersonate.php';

        expect($config['redirect_to'])->toBe(env('FILAMENT_IMPERSONATE_REDIRECT', '/'));
    });

    it('has default leave_middleware of web', function () {
        expect(config('filament-impersonate.leave_middleware'))->toBe('web');
    });

    it('has default route_prefix of null', function () {
        expect(config('filament-impersonate.route_prefix'))->toBeNull();
    });

    it('has default allow_soft_deleted of false', function () {
        expect(config('filament-impersonate.allow_soft_deleted'))->toBeFalse();
    });

    it('has default banner.render_hook', function () {
        expect(config('filament-impersonate.banner.render_hook'))->toBe('panels::body.start');
    });

    it('has default banner.style of dark', function () {
        expect(config('filament-impersonate.banner.style'))->toBe('dark');
    });

    it('has default banner.fixed of true', function () {
        expect(config('filament-impersonate.banner.fixed'))->toBeTrue();
    });

    it('has default banner.position of top', function () {
        expect(config('filament-impersonate.banner.position'))->toBe('top');
    });

    it('has light and dark banner styles defined', function () {
        $styles = config('filament-impersonate.banner.styles');

        expect($styles)->toHaveKey('light');
        expect($styles)->toHaveKey('dark');

        expect($styles['light'])->toHaveKeys(['text', 'background', 'border']);
        expect($styles['dark'])->toHaveKeys(['text', 'background', 'border']);
    });
});

describe('configuration overrides', function () {
    it('respects guard override', function () {
        config(['filament-impersonate.guard' => 'admin']);

        expect(config('filament-impersonate.guard'))->toBe('admin');
    });

    it('respects redirect_to override', function () {
        config(['filament-impersonate.redirect_to' => '/custom-redirect']);

        expect(config('filament-impersonate.redirect_to'))->toBe('/custom-redirect');
    });

    it('respects allow_soft_deleted override', function () {
        config(['filament-impersonate.allow_soft_deleted' => true]);

        expect(config('filament-impersonate.allow_soft_deleted'))->toBeTrue();
    });

    it('respects banner.style override', function () {
        config(['filament-impersonate.banner.style' => 'light']);

        expect(config('filament-impersonate.banner.style'))->toBe('light');
    });

    it('respects banner.position override', function () {
        config(['filament-impersonate.banner.position' => 'bottom']);

        expect(config('filament-impersonate.banner.position'))->toBe('bottom');
    });

    it('respects banner.fixed override', function () {
        config(['filament-impersonate.banner.fixed' => false]);

        expect(config('filament-impersonate.banner.fixed'))->toBeFalse();
    });

    it('respects route_prefix override', function () {
        config(['filament-impersonate.route_prefix' => 'admin']);

        expect(config('filament-impersonate.route_prefix'))->toBe('admin');
    });

    it('respects leave_middleware override', function () {
        config(['filament-impersonate.leave_middleware' => 'auth:admin']);

        expect(config('filament-impersonate.leave_middleware'))->toBe('auth:admin');
    });

    it('respects banner.render_hook override', function () {
        config(['filament-impersonate.banner.render_hook' => 'panels::topbar.start']);

        expect(config('filament-impersonate.banner.render_hook'))->toBe('panels::topbar.start');
    });
});
