<?php

use STS\FilamentImpersonate\Events\EnterImpersonation;
use STS\FilamentImpersonate\Events\LeaveImpersonation;
use STS\FilamentImpersonate\Facades\Impersonation;
use STS\FilamentImpersonate\Guard\SessionGuard;
use STS\FilamentImpersonate\ImpersonateManager;
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
    if (Impersonation::isImpersonating()) {
        Impersonation::leave();
    }
});

describe('isImpersonating', function () {
    it('returns false when not impersonating', function () {
        expect(Impersonation::isImpersonating())->toBeFalse();
    });

    it('returns true after taking impersonation', function () {
        Impersonation::enter($this->admin, $this->targetUser, 'web');

        expect(Impersonation::isImpersonating())->toBeTrue();
    });

    it('returns false after leaving impersonation', function () {
        Impersonation::enter($this->admin, $this->targetUser, 'web');
        Impersonation::leave();

        expect(Impersonation::isImpersonating())->toBeFalse();
    });
});

describe('enter', function () {
    it('returns true on success', function () {
        $result = Impersonation::enter($this->admin, $this->targetUser, 'web');

        expect($result)->toBeTrue();
    });

    it('switches the authenticated user', function () {
        expect(auth()->id())->toBe($this->admin->id);

        Impersonation::enter($this->admin, $this->targetUser, 'web');

        expect(auth()->id())->toBe($this->targetUser->id);
    });

    it('stores impersonator id in session', function () {
        Impersonation::enter($this->admin, $this->targetUser, 'web');

        expect(session(ImpersonateManager::SESSION_KEY))->toBe($this->admin->id);
    });

    it('stores guard names in session', function () {
        Impersonation::enter($this->admin, $this->targetUser, 'web');

        expect(session(ImpersonateManager::SESSION_GUARD))->toBe('web');
        expect(session(ImpersonateManager::SESSION_GUARD_USING))->toBe('web');
    });

    it('fires EnterImpersonation event', function () {
        Event::fake([EnterImpersonation::class]);

        Impersonation::enter($this->admin, $this->targetUser, 'web');

        Event::assertDispatched(EnterImpersonation::class, function ($event) {
            return $event->impersonator->is($this->admin)
                && $event->impersonated->is($this->targetUser);
        });
    });
});

describe('leave', function () {
    it('returns true on success', function () {
        Impersonation::enter($this->admin, $this->targetUser, 'web');
        $result = Impersonation::leave();

        expect($result)->toBeTrue();
    });

    it('restores the original user', function () {
        Impersonation::enter($this->admin, $this->targetUser, 'web');

        expect(auth()->id())->toBe($this->targetUser->id);

        Impersonation::leave();

        expect(auth()->id())->toBe($this->admin->id);
    });

    it('clears impersonation session data', function () {
        Impersonation::enter($this->admin, $this->targetUser, 'web');
        Impersonation::leave();

        expect(session(ImpersonateManager::SESSION_KEY))->toBeNull();
        expect(session(ImpersonateManager::SESSION_GUARD))->toBeNull();
        expect(session(ImpersonateManager::SESSION_GUARD_USING))->toBeNull();
    });

    it('fires LeaveImpersonation event', function () {
        Impersonation::enter($this->admin, $this->targetUser, 'web');

        Event::fake([LeaveImpersonation::class]);

        Impersonation::leave();

        Event::assertDispatched(LeaveImpersonation::class, function ($event) {
            return $event->impersonator->is($this->admin)
                && $event->impersonated->is($this->targetUser);
        });
    });
});

describe('getImpersonator', function () {
    it('returns null when not impersonating', function () {
        expect(Impersonation::getImpersonator())->toBeNull();
    });

    it('returns the impersonator user when impersonating', function () {
        Impersonation::enter($this->admin, $this->targetUser, 'web');

        $impersonator = Impersonation::getImpersonator();

        expect($impersonator)->toBeInstanceOf(User::class);
        expect($impersonator->id)->toBe($this->admin->id);
    });
});

describe('getImpersonatorId', function () {
    it('returns null when not impersonating', function () {
        expect(Impersonation::getImpersonatorId())->toBeNull();
    });

    it('returns the impersonator id when impersonating', function () {
        Impersonation::enter($this->admin, $this->targetUser, 'web');

        expect(Impersonation::getImpersonatorId())->toBe($this->admin->id);
    });
});

describe('guard names', function () {
    it('returns null for guard names when not impersonating', function () {
        expect(Impersonation::getImpersonatorGuardName())->toBeNull();
        expect(Impersonation::getImpersonatorGuardUsingName())->toBeNull();
    });

    it('returns correct guard names when impersonating', function () {
        Impersonation::enter($this->admin, $this->targetUser, 'web');

        expect(Impersonation::getImpersonatorGuardName())->toBe('web');
        expect(Impersonation::getImpersonatorGuardUsingName())->toBe('web');
    });
});

describe('clear', function () {
    it('removes all impersonation session data', function () {
        Impersonation::enter($this->admin, $this->targetUser, 'web');

        expect(session()->has(ImpersonateManager::SESSION_KEY))->toBeTrue();

        Impersonation::clear();

        expect(session()->has(ImpersonateManager::SESSION_KEY))->toBeFalse();
        expect(session()->has(ImpersonateManager::SESSION_GUARD))->toBeFalse();
        expect(session()->has(ImpersonateManager::SESSION_GUARD_USING))->toBeFalse();
    });
});

describe('error handling', function () {
    it('clears session state when enter() fails', function () {
        config(['auth.guards.token_guard' => ['driver' => 'token', 'provider' => 'users']]);

        $result = Impersonation::enter($this->admin, $this->targetUser, 'token_guard');

        expect($result)->toBeFalse();
        expect(Impersonation::isImpersonating())->toBeFalse();
    });

    it('returns false when impersonator cannot be found during leave()', function () {
        Impersonation::enter($this->admin, $this->targetUser, 'web');

        $this->admin->forceDelete();

        $result = Impersonation::leave();

        expect($result)->toBeFalse();
        expect(Impersonation::isImpersonating())->toBeFalse();
    });
});

describe('SessionGuard integration', function () {
    it('uses our custom SessionGuard', function () {
        expect(auth()->guard('web'))->toBeInstanceOf(SessionGuard::class);
    });

    it('SessionGuard has quietLogin method', function () {
        expect(method_exists(auth()->guard('web'), 'quietLogin'))->toBeTrue();
    });

    it('SessionGuard has quietLogout method', function () {
        expect(method_exists(auth()->guard('web'), 'quietLogout'))->toBeTrue();
    });
});

describe('scoped binding (Octane safety)', function () {
    it('is registered as scoped binding', function () {
        $manager1 = app(ImpersonateManager::class);
        $manager2 = app(ImpersonateManager::class);

        expect($manager1)->toBe($manager2);
    });

    it('is stateless - reads from session each call', function () {
        expect(Impersonation::isImpersonating())->toBeFalse();

        session()->put(ImpersonateManager::SESSION_KEY, 999);

        expect(Impersonation::isImpersonating())->toBeTrue();

        session()->forget(ImpersonateManager::SESSION_KEY);
    });

    it('is available via impersonate alias', function () {
        expect(app('impersonate'))->toBeInstanceOf(ImpersonateManager::class);
    });
});
