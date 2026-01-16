<?php

use Illuminate\Http\RedirectResponse;
use Lab404\Impersonate\Services\ImpersonateManager;
use Livewire\Features\SupportRedirects\Redirector;
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

describe('impersonate method', function () {
    it('returns false when canImpersonate fails', function () {
        User::$canImpersonateResult = false;

        $action = Impersonate::make();
        $result = $action->impersonate($this->targetUser);

        expect($result)->toBeFalse();
    });

    it('returns redirect response on success', function () {
        $action = Impersonate::make();
        $result = $action->impersonate($this->targetUser);

        expect($result)->toBeInstanceOf(RedirectResponse::class);
    });

    it('redirects to configured redirectTo', function () {
        $action = Impersonate::make()->redirectTo('/custom-dashboard');
        $result = $action->impersonate($this->targetUser);

        expect($result)->toBeInstanceOf(RedirectResponse::class);
        expect($result->getTargetUrl())->toEndWith('/custom-dashboard');
    });

    it('redirects to config default when redirectTo not set', function () {
        config(['filament-impersonate.redirect_to' => '/config-default']);

        $action = Impersonate::make();
        $result = $action->impersonate($this->targetUser);

        expect($result)->toBeInstanceOf(RedirectResponse::class);
        expect($result->getTargetUrl())->toEndWith('/config-default');
    });

    it('sets session impersonate.guard', function () {
        $action = Impersonate::make()->guard('custom-guard');
        $action->impersonate($this->targetUser);

        expect(session('impersonate.guard'))->toBe('custom-guard');
    });

    it('sets session impersonate.back_to from backTo method', function () {
        $action = Impersonate::make()->backTo('/return-here');
        $action->impersonate($this->targetUser);

        expect(session('impersonate.back_to'))->toBe('/return-here');
    });

    it('sets session impersonate.back_to from referer when backTo not set', function () {
        request()->headers->set('referer', '/came-from-here');

        $action = Impersonate::make();
        $action->impersonate($this->targetUser);

        expect(session('impersonate.back_to'))->toBe('/came-from-here');
    });

    it('starts impersonation via ImpersonateManager', function () {
        $action = Impersonate::make();
        $action->impersonate($this->targetUser);

        expect(app(ImpersonateManager::class)->isImpersonating())->toBeTrue();
    });

    it('impersonates the correct user', function () {
        $action = Impersonate::make();
        $action->impersonate($this->targetUser);

        expect(auth()->id())->toBe($this->targetUser->id);
    });

    it('returns false when trying to impersonate yourself', function () {
        $action = Impersonate::make();
        $result = $action->impersonate($this->admin);

        expect($result)->toBeFalse();
    });

    it('returns false when target is soft deleted', function () {
        $this->targetUser->delete();

        $action = Impersonate::make();
        $result = $action->impersonate($this->targetUser);

        expect($result)->toBeFalse();
    });

    it('succeeds when target is soft deleted and config allows it', function () {
        config(['filament-impersonate.allow_soft_deleted' => true]);
        $this->targetUser->delete();

        $action = Impersonate::make();
        $result = $action->impersonate($this->targetUser);

        expect($result)->toBeInstanceOf(RedirectResponse::class);
    });

    it('uses default guard from panel when not specified', function () {
        $action = Impersonate::make();
        $action->impersonate($this->targetUser);

        // The panel is configured with 'web' guard in TestCase
        expect(session('impersonate.guard'))->toBe('web');
    });

    it('evaluates closure for redirectTo', function () {
        $action = Impersonate::make()
            ->redirectTo(fn () => '/dynamic-' . $this->targetUser->id);

        $result = $action->impersonate($this->targetUser);

        expect($result->getTargetUrl())->toEndWith('/dynamic-' . $this->targetUser->id);
    });

    it('evaluates closure for backTo', function () {
        $action = Impersonate::make()
            ->backTo(fn () => '/dynamic-back-' . $this->admin->id);

        $action->impersonate($this->targetUser);

        expect(session('impersonate.back_to'))->toBe('/dynamic-back-' . $this->admin->id);
    });

    it('evaluates closure for guard', function () {
        $action = Impersonate::make()
            ->guard(fn () => 'dynamic-guard');

        $action->impersonate($this->targetUser);

        expect(session('impersonate.guard'))->toBe('dynamic-guard');
    });
});
