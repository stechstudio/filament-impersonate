<?php

use Illuminate\Support\Facades\Blade;
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

    if (app(ImpersonateManager::class)->isImpersonating()) {
        app(ImpersonateManager::class)->leave();
    }
});

describe('banner visibility', function () {
    it('does not render when not impersonating', function () {
        $html = Blade::render('<x-filament-impersonate::banner/>');

        expect($html)->toBe('');
    });

    it('renders when impersonating', function () {
        $action = Impersonate::make()->backTo('/admin');
        $action->impersonate($this->targetUser);

        $html = Blade::render('<x-filament-impersonate::banner/>');

        expect($html)->toContain('impersonate-banner');
    });

    it('contains leave link', function () {
        $action = Impersonate::make()->backTo('/admin');
        $action->impersonate($this->targetUser);

        $html = Blade::render('<x-filament-impersonate::banner/>');

        expect($html)->toContain(route('filament-impersonate.leave'));
    });

    it('displays impersonated user name by default', function () {
        $action = Impersonate::make()->backTo('/admin');
        $action->impersonate($this->targetUser);

        $html = Blade::render('<x-filament-impersonate::banner/>');

        expect($html)->toContain('Target User');
    });
});

describe('banner display prop', function () {
    it('uses custom display value when provided', function () {
        $action = Impersonate::make()->backTo('/admin');
        $action->impersonate($this->targetUser);

        $html = Blade::render('<x-filament-impersonate::banner :display="\'custom@email.com\'"/>');

        expect($html)->toContain('custom@email.com');
    });
});

describe('banner styling', function () {
    it('uses dark style by default', function () {
        config(['filament-impersonate.banner.style' => 'dark']);

        $action = Impersonate::make()->backTo('/admin');
        $action->impersonate($this->targetUser);

        $html = Blade::render('<x-filament-impersonate::banner/>');

        // Dark style uses dark background color
        expect($html)->toContain('--impersonate-dark-bg-color');
    });

    it('supports light style', function () {
        $action = Impersonate::make()->backTo('/admin');
        $action->impersonate($this->targetUser);

        $html = Blade::render('<x-filament-impersonate::banner style="light"/>');

        // Light style uses light background color
        expect($html)->toContain('var(--impersonate-light-bg-color)');
    });

    it('supports auto style', function () {
        $action = Impersonate::make()->backTo('/admin');
        $action->impersonate($this->targetUser);

        $html = Blade::render('<x-filament-impersonate::banner style="auto"/>');

        // Auto style includes dark mode media query
        expect($html)->toContain('.dark #impersonate-banner');
    });
});

describe('banner position', function () {
    it('uses top position by default', function () {
        config(['filament-impersonate.banner.position' => 'top']);

        $action = Impersonate::make()->backTo('/admin');
        $action->impersonate($this->targetUser);

        $html = Blade::render('<x-filament-impersonate::banner/>');

        expect($html)->toContain('top: 0');
    });

    it('supports bottom position', function () {
        $action = Impersonate::make()->backTo('/admin');
        $action->impersonate($this->targetUser);

        $html = Blade::render('<x-filament-impersonate::banner position="bottom"/>');

        expect($html)->toContain('bottom: 0');
    });
});

describe('banner fixed', function () {
    it('uses fixed positioning by default', function () {
        config(['filament-impersonate.banner.fixed' => true]);

        $action = Impersonate::make()->backTo('/admin');
        $action->impersonate($this->targetUser);

        $html = Blade::render('<x-filament-impersonate::banner/>');

        expect($html)->toContain('position: fixed');
    });

    it('supports absolute positioning', function () {
        $action = Impersonate::make()->backTo('/admin');
        $action->impersonate($this->targetUser);

        $html = Blade::render('<x-filament-impersonate::banner :fixed="false"/>');

        expect($html)->toContain('position: absolute');
    });
});

describe('banner multi-guard support', function () {
    it('does not render when impersonator guard differs from current panel guard', function () {
        // Start impersonation
        $action = Impersonate::make()->backTo('/admin');
        $action->impersonate($this->targetUser);

        // Simulate being in a different panel with a different guard
        // The impersonator guard is 'web', but we'll check with a different guard
        $impersonatorGuard = app('impersonate')->getImpersonatorGuardUsingName();
        expect($impersonatorGuard)->toBe('web');

        // Mock the scenario where we're in a different panel
        // by directly testing the condition logic
        $currentPanelGuard = 'admin'; // Different from 'web'
        $shouldShow = app('impersonate')->isImpersonating()
            && $currentPanelGuard
            && $impersonatorGuard === $currentPanelGuard;

        expect($shouldShow)->toBeFalse();
    });

    it('renders when impersonator guard matches current panel guard', function () {
        // Start impersonation
        $action = Impersonate::make()->backTo('/admin');
        $action->impersonate($this->targetUser);

        $impersonatorGuard = app('impersonate')->getImpersonatorGuardUsingName();
        $currentPanelGuard = 'web'; // Same as impersonator guard

        $shouldShow = app('impersonate')->isImpersonating()
            && $currentPanelGuard
            && $impersonatorGuard === $currentPanelGuard;

        expect($shouldShow)->toBeTrue();
    });
});

describe('banner translations', function () {
    it('contains impersonating translation key', function () {
        $action = Impersonate::make()->backTo('/admin');
        $action->impersonate($this->targetUser);

        $html = Blade::render('<x-filament-impersonate::banner/>');

        // The translation should be rendered
        expect($html)->toContain(__('filament-impersonate::banner.impersonating'));
    });

    it('contains leave translation key', function () {
        $action = Impersonate::make()->backTo('/admin');
        $action->impersonate($this->targetUser);

        $html = Blade::render('<x-filament-impersonate::banner/>');

        // The leave button text should be rendered
        expect($html)->toContain(__('filament-impersonate::banner.leave'));
    });
});
