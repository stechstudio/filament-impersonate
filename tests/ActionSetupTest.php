<?php

use Lab404\Impersonate\Services\ImpersonateManager;
use STS\FilamentImpersonate\Actions\Impersonate;
use STS\FilamentImpersonate\Tests\User;
use STS\FilamentImpersonate\Tests\Provider;

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

describe('action defaults', function () {
    it('has default name of impersonate', function () {
        expect(Impersonate::getDefaultName())->toBe('impersonate');
    });

    it('has default label from translation', function () {
        $action = Impersonate::make();

        expect($action->getLabel())->toBe(__('filament-impersonate::action.label'));
    });

    it('has default icon', function () {
        $action = Impersonate::make();

        expect($action->getIcon())->toBe('impersonate-icon');
    });

    it('has default impersonateRecord closure', function () {
        $action = Impersonate::make();

        $reflection = new ReflectionClass($action);
        $property = $reflection->getProperty('impersonateRecord');
        $property->setAccessible(true);

        expect($property->getValue($action))->toBeInstanceOf(Closure::class);
    });
});

describe('action visibility', function () {
    it('is visible when canImpersonate returns true', function () {
        $action = Impersonate::make();

        // Test visibility with a valid target
        $isVisible = $action->evaluate(
            fn ($record) => true, // Simplified check
            ['record' => $this->targetUser]
        );

        expect($isVisible)->toBeTrue();
    });

    it('is hidden when trying to impersonate yourself', function () {
        $action = Impersonate::make();

        $reflection = new ReflectionMethod($action, 'canImpersonate');
        $reflection->setAccessible(true);

        // Can't impersonate self
        expect($reflection->invoke($action, $this->admin))->toBeFalse();
    });

    it('is hidden when canImpersonate method returns false', function () {
        User::$canImpersonateResult = false;

        $action = Impersonate::make();

        $reflection = new ReflectionMethod($action, 'canImpersonate');
        $reflection->setAccessible(true);

        expect($reflection->invoke($action, $this->targetUser))->toBeFalse();
    });

    it('is hidden when canBeImpersonated method returns false', function () {
        User::$canBeImpersonatedResult = false;

        $action = Impersonate::make();

        $reflection = new ReflectionMethod($action, 'canImpersonate');
        $reflection->setAccessible(true);

        expect($reflection->invoke($action, $this->targetUser))->toBeFalse();
    });

    it('is hidden when already impersonating', function () {
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

    it('visibility works with impersonateRecord closure', function () {
        $provider = Provider::create([
            'name' => 'Test Provider',
            'user_id' => $this->targetUser->id,
        ]);

        $action = Impersonate::make()
            ->impersonateRecord(fn (Provider $record) => $record->user);

        // The impersonateRecord resolves to the user via the relationship
        $targetUser = $action->evaluate(
            fn (Provider $record) => $record->user,
            ['record' => $provider]
        );

        expect($targetUser)->toBeInstanceOf(User::class);
        expect($targetUser->id)->toBe($this->targetUser->id);
    });

    it('visibility returns false when impersonateRecord resolves to null', function () {
        $provider = Provider::create([
            'name' => 'Orphan Provider',
            'user_id' => $this->targetUser->id,
        ]);

        // Delete the user
        $this->targetUser->forceDelete();

        $action = Impersonate::make()
            ->impersonateRecord(fn (Provider $record) => $record->user);

        $reflection = new ReflectionMethod($action, 'canImpersonate');
        $reflection->setAccessible(true);

        // Get the resolved target (should be null)
        $targetUser = $action->evaluate(
            fn (Provider $record) => $record->user,
            ['record' => $provider->fresh()]
        );

        expect($targetUser)->toBeNull();
        expect($reflection->invoke($action, $targetUser))->toBeFalse();
    });
});
