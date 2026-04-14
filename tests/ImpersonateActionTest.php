<?php

use STS\FilamentImpersonate\Actions\Impersonate;
use STS\FilamentImpersonate\Tests\User;
use STS\FilamentImpersonate\Tests\Provider;

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

describe('redirectTo', function () {
    it('returns custom redirect URL when set', function () {
        $action = Impersonate::make()
            ->redirectTo('/custom-path');

        expect($action->getRedirectTo())->toBe('/custom-path');
    });

    it('falls back to config when redirectTo not set', function () {
        $action = Impersonate::make();

        expect($action->getRedirectTo())->toBe('/default-redirect');
    });

    it('evaluates closure for redirectTo', function () {
        $action = Impersonate::make()
            ->redirectTo(fn () => '/dynamic-path');

        expect($action->getRedirectTo())->toBe('/dynamic-path');
    });
});

describe('impersonateRecord', function () {
    it('defaults to using the record directly', function () {
        $action = Impersonate::make();

        // The default is fn($record) => $record
        // We test this indirectly through the evaluate mechanism
        $reflection = new ReflectionClass($action);
        $property = $reflection->getProperty('impersonateRecord');
        $property->setAccessible(true);

        $closure = $property->getValue($action);
        expect($closure)->toBeInstanceOf(Closure::class);
    });

    it('accepts closure that returns related model', function () {
        $provider = Provider::create([
            'name' => 'Test Provider',
            'user_id' => $this->targetUser->id,
        ]);

        $action = Impersonate::make()
            ->impersonateRecord(fn (Provider $record) => $record->user);

        // Verify the closure is set
        $reflection = new ReflectionClass($action);
        $property = $reflection->getProperty('impersonateRecord');
        $property->setAccessible(true);

        $closure = $property->getValue($action);

        // Evaluate the closure manually with the provider
        $result = $closure($provider);
        expect($result)->toBeInstanceOf(User::class);
        expect($result->id)->toBe($this->targetUser->id);
    });
});

describe('guard', function () {
    it('returns custom guard when set', function () {
        $action = Impersonate::make()
            ->guard('admin');

        expect($action->getGuard())->toBe('admin');
    });

    it('evaluates closure for guard', function () {
        $action = Impersonate::make()
            ->guard(fn () => 'custom-guard');

        expect($action->getGuard())->toBe('custom-guard');
    });
});

describe('spa', function () {
    it('returns null when spa not set', function () {
        $action = Impersonate::make()
            ->redirectTo('/custom-path');

        expect($action->getRedirectSpa())->toBeNull();
    });

    it('returns true when spa() called with no argument', function () {
        $action = Impersonate::make()
            ->redirectTo('/custom-path')
            ->spa();

        expect($action->getRedirectSpa())->toBeTrue();
    });

    it('returns true when withSpa() called', function () {
        $action = Impersonate::make()
            ->redirectTo('/custom-path')
            ->withSpa();

        expect($action->getRedirectSpa())->toBeTrue();
    });

    it('returns false when withoutSpa() called', function () {
        $action = Impersonate::make()
            ->redirectTo('/custom-path')
            ->withoutSpa();

        expect($action->getRedirectSpa())->toBeFalse();
    });

    it('returns false when spa(false) called', function () {
        $action = Impersonate::make()
            ->redirectTo('/custom-path')
            ->spa(false);

        expect($action->getRedirectSpa())->toBeFalse();
    });

    it('evaluates closure for spa', function () {
        $action = Impersonate::make()
            ->redirectTo('/custom-path')
            ->spa(fn () => false);

        expect($action->getRedirectSpa())->toBeFalse();
    });
});

describe('backTo', function () {
    it('returns custom backTo URL when set', function () {
        $action = Impersonate::make()
            ->backTo('/return-path');

        expect($action->getBackTo())->toBe('/return-path');
    });

    it('returns null when backTo not set', function () {
        $action = Impersonate::make();

        expect($action->getBackTo())->toBeNull();
    });
});
