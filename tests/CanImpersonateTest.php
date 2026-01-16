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

describe('canImpersonate authorization', function () {
    it('returns false when target is null', function () {
        $action = Impersonate::make();

        $reflection = new ReflectionMethod($action, 'canImpersonate');
        $reflection->setAccessible(true);

        expect($reflection->invoke($action, null))->toBeFalse();
    });

    it('returns false when target is empty', function () {
        $action = Impersonate::make();

        $reflection = new ReflectionMethod($action, 'canImpersonate');
        $reflection->setAccessible(true);

        expect($reflection->invoke($action, ''))->toBeFalse();
    });

    it('returns false when trying to impersonate yourself', function () {
        $action = Impersonate::make();

        $reflection = new ReflectionMethod($action, 'canImpersonate');
        $reflection->setAccessible(true);

        expect($reflection->invoke($action, $this->admin))->toBeFalse();
    });

    it('returns true when impersonating a different user', function () {
        $action = Impersonate::make();

        $reflection = new ReflectionMethod($action, 'canImpersonate');
        $reflection->setAccessible(true);

        expect($reflection->invoke($action, $this->targetUser))->toBeTrue();
    });

    it('returns false when already impersonating', function () {
        // Start impersonation
        app(ImpersonateManager::class)->take($this->admin, $this->targetUser, 'web');

        $thirdUser = User::create([
            'name' => 'Third User',
            'email' => 'third@example.com',
            'password' => bcrypt('password'),
        ]);

        $action = Impersonate::make();

        $reflection = new ReflectionMethod($action, 'canImpersonate');
        $reflection->setAccessible(true);

        expect($reflection->invoke($action, $thirdUser))->toBeFalse();
    });

    it('returns false when target is soft deleted by default', function () {
        $this->targetUser->delete();

        $action = Impersonate::make();

        $reflection = new ReflectionMethod($action, 'canImpersonate');
        $reflection->setAccessible(true);

        expect($reflection->invoke($action, $this->targetUser))->toBeFalse();
    });

    it('returns true when target is soft deleted and config allows it', function () {
        config(['filament-impersonate.allow_soft_deleted' => true]);

        $this->targetUser->delete();

        $action = Impersonate::make();

        $reflection = new ReflectionMethod($action, 'canImpersonate');
        $reflection->setAccessible(true);

        expect($reflection->invoke($action, $this->targetUser))->toBeTrue();
    });

    it('returns false when impersonator canImpersonate returns false', function () {
        User::$canImpersonateResult = false;

        $action = Impersonate::make();

        $reflection = new ReflectionMethod($action, 'canImpersonate');
        $reflection->setAccessible(true);

        expect($reflection->invoke($action, $this->targetUser))->toBeFalse();
    });

    it('returns true when impersonator canImpersonate returns true', function () {
        User::$canImpersonateResult = true;

        $action = Impersonate::make();

        $reflection = new ReflectionMethod($action, 'canImpersonate');
        $reflection->setAccessible(true);

        expect($reflection->invoke($action, $this->targetUser))->toBeTrue();
    });

    it('returns false when target canBeImpersonated returns false', function () {
        User::$canBeImpersonatedResult = false;

        $action = Impersonate::make();

        $reflection = new ReflectionMethod($action, 'canImpersonate');
        $reflection->setAccessible(true);

        expect($reflection->invoke($action, $this->targetUser))->toBeFalse();
    });

    it('returns true when target canBeImpersonated returns true', function () {
        User::$canBeImpersonatedResult = true;

        $action = Impersonate::make();

        $reflection = new ReflectionMethod($action, 'canImpersonate');
        $reflection->setAccessible(true);

        expect($reflection->invoke($action, $this->targetUser))->toBeTrue();
    });
});
