<?php

use STS\FilamentImpersonate\Events\LeaveImpersonation;
use STS\FilamentImpersonate\Events\TakeImpersonation;
use STS\FilamentImpersonate\Guard\SessionGuard;
use STS\FilamentImpersonate\Services\ImpersonateManager;
use STS\FilamentImpersonate\Tests\User;
use Illuminate\Support\Facades\Event;

beforeEach(function () {
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
    if (app(ImpersonateManager::class)->isImpersonating()) {
        app(ImpersonateManager::class)->leave();
    }
});

describe('isImpersonating', function () {
    it('returns false when not impersonating', function () {
        expect(app(ImpersonateManager::class)->isImpersonating())->toBeFalse();
    });

    it('returns true after taking impersonation', function () {
        app(ImpersonateManager::class)->take($this->admin, $this->targetUser, 'web');

        expect(app(ImpersonateManager::class)->isImpersonating())->toBeTrue();
    });

    it('returns false after leaving impersonation', function () {
        app(ImpersonateManager::class)->take($this->admin, $this->targetUser, 'web');
        app(ImpersonateManager::class)->leave();

        expect(app(ImpersonateManager::class)->isImpersonating())->toBeFalse();
    });
});

describe('take', function () {
    it('returns true on success', function () {
        $result = app(ImpersonateManager::class)->take($this->admin, $this->targetUser, 'web');

        expect($result)->toBeTrue();
    });

    it('switches the authenticated user', function () {
        expect(auth()->id())->toBe($this->admin->id);

        app(ImpersonateManager::class)->take($this->admin, $this->targetUser, 'web');

        expect(auth()->id())->toBe($this->targetUser->id);
    });

    it('stores impersonator id in session', function () {
        app(ImpersonateManager::class)->take($this->admin, $this->targetUser, 'web');

        expect(session(ImpersonateManager::SESSION_KEY))->toBe($this->admin->id);
    });

    it('stores guard names in session', function () {
        app(ImpersonateManager::class)->take($this->admin, $this->targetUser, 'web');

        expect(session(ImpersonateManager::SESSION_GUARD))->toBe('web');
        expect(session(ImpersonateManager::SESSION_GUARD_USING))->toBe('web');
    });

    it('fires TakeImpersonation event', function () {
        Event::fake([TakeImpersonation::class]);

        app(ImpersonateManager::class)->take($this->admin, $this->targetUser, 'web');

        Event::assertDispatched(TakeImpersonation::class, function ($event) {
            return $event->impersonator->is($this->admin)
                && $event->impersonated->is($this->targetUser);
        });
    });
});

describe('leave', function () {
    it('returns true on success', function () {
        app(ImpersonateManager::class)->take($this->admin, $this->targetUser, 'web');
        $result = app(ImpersonateManager::class)->leave();

        expect($result)->toBeTrue();
    });

    it('restores the original user', function () {
        app(ImpersonateManager::class)->take($this->admin, $this->targetUser, 'web');

        expect(auth()->id())->toBe($this->targetUser->id);

        app(ImpersonateManager::class)->leave();

        expect(auth()->id())->toBe($this->admin->id);
    });

    it('clears impersonation session data', function () {
        app(ImpersonateManager::class)->take($this->admin, $this->targetUser, 'web');
        app(ImpersonateManager::class)->leave();

        expect(session(ImpersonateManager::SESSION_KEY))->toBeNull();
        expect(session(ImpersonateManager::SESSION_GUARD))->toBeNull();
        expect(session(ImpersonateManager::SESSION_GUARD_USING))->toBeNull();
    });

    it('fires LeaveImpersonation event', function () {
        app(ImpersonateManager::class)->take($this->admin, $this->targetUser, 'web');

        Event::fake([LeaveImpersonation::class]);

        app(ImpersonateManager::class)->leave();

        Event::assertDispatched(LeaveImpersonation::class, function ($event) {
            return $event->impersonator->is($this->admin)
                && $event->impersonated->is($this->targetUser);
        });
    });
});

describe('getImpersonator', function () {
    it('returns null when not impersonating', function () {
        expect(app(ImpersonateManager::class)->getImpersonator())->toBeNull();
    });

    it('returns the impersonator user when impersonating', function () {
        app(ImpersonateManager::class)->take($this->admin, $this->targetUser, 'web');

        $impersonator = app(ImpersonateManager::class)->getImpersonator();

        expect($impersonator)->toBeInstanceOf(User::class);
        expect($impersonator->id)->toBe($this->admin->id);
    });
});

describe('getImpersonatorId', function () {
    it('returns null when not impersonating', function () {
        expect(app(ImpersonateManager::class)->getImpersonatorId())->toBeNull();
    });

    it('returns the impersonator id when impersonating', function () {
        app(ImpersonateManager::class)->take($this->admin, $this->targetUser, 'web');

        expect(app(ImpersonateManager::class)->getImpersonatorId())->toBe($this->admin->id);
    });
});

describe('guard names', function () {
    it('returns null for guard names when not impersonating', function () {
        expect(app(ImpersonateManager::class)->getImpersonatorGuardName())->toBeNull();
        expect(app(ImpersonateManager::class)->getImpersonatorGuardUsingName())->toBeNull();
    });

    it('returns correct guard names when impersonating', function () {
        app(ImpersonateManager::class)->take($this->admin, $this->targetUser, 'web');

        expect(app(ImpersonateManager::class)->getImpersonatorGuardName())->toBe('web');
        expect(app(ImpersonateManager::class)->getImpersonatorGuardUsingName())->toBe('web');
    });
});

describe('clear', function () {
    it('removes all impersonation session data', function () {
        app(ImpersonateManager::class)->take($this->admin, $this->targetUser, 'web');

        expect(session()->has(ImpersonateManager::SESSION_KEY))->toBeTrue();

        app(ImpersonateManager::class)->clear();

        expect(session()->has(ImpersonateManager::SESSION_KEY))->toBeFalse();
        expect(session()->has(ImpersonateManager::SESSION_GUARD))->toBeFalse();
        expect(session()->has(ImpersonateManager::SESSION_GUARD_USING))->toBeFalse();
    });
});

describe('error handling', function () {
    it('clears session state when take() fails', function () {
        // Manually set a non-session guard to force a failure
        config(['auth.guards.token_guard' => ['driver' => 'token', 'provider' => 'users']]);

        // Attempt impersonation with a guard that is not a session guard
        $result = app(ImpersonateManager::class)->take($this->admin, $this->targetUser, 'token_guard');

        expect($result)->toBeFalse();

        // Session should be clean - no stale impersonation keys
        expect(app(ImpersonateManager::class)->isImpersonating())->toBeFalse();
    });

    it('returns false when impersonator cannot be found during leave()', function () {
        app(ImpersonateManager::class)->take($this->admin, $this->targetUser, 'web');

        // Delete the impersonator to simulate missing user
        $this->admin->forceDelete();

        $result = app(ImpersonateManager::class)->leave();

        expect($result)->toBeFalse();

        // Session should be cleaned up
        expect(app(ImpersonateManager::class)->isImpersonating())->toBeFalse();
    });
});

describe('SessionGuard integration', function () {
    it('uses our custom SessionGuard', function () {
        $guard = auth()->guard('web');

        expect($guard)->toBeInstanceOf(SessionGuard::class);
    });

    it('SessionGuard has quietLogin method', function () {
        $guard = auth()->guard('web');

        expect(method_exists($guard, 'quietLogin'))->toBeTrue();
    });

    it('SessionGuard has quietLogout method', function () {
        $guard = auth()->guard('web');

        expect(method_exists($guard, 'quietLogout'))->toBeTrue();
    });
});

describe('scoped binding (Octane safety)', function () {
    it('is registered as scoped binding', function () {
        $manager1 = app(ImpersonateManager::class);
        $manager2 = app(ImpersonateManager::class);

        // Within the same request, scoped returns the same instance
        expect($manager1)->toBe($manager2);
    });

    it('is stateless - reads from session each call', function () {
        $manager = app(ImpersonateManager::class);

        expect($manager->isImpersonating())->toBeFalse();

        // Simulate session state change
        session()->put(ImpersonateManager::SESSION_KEY, 999);

        // Manager reads from session, not cached state
        expect($manager->isImpersonating())->toBeTrue();

        session()->forget(ImpersonateManager::SESSION_KEY);
    });

    it('is available via impersonate alias', function () {
        expect(app('impersonate'))->toBeInstanceOf(ImpersonateManager::class);
    });
});
