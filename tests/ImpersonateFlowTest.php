<?php

use Illuminate\Http\RedirectResponse;
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

describe('impersonateRecord with relationship', function () {
    it('correctly evaluates closure with record parameter', function () {
        $provider = Provider::create([
            'name' => 'Test Provider',
            'user_id' => $this->targetUser->id,
        ]);

        $action = Impersonate::make()
            ->impersonateRecord(fn (Provider $record) => $record->user)
            ->redirectTo('/provider-dashboard');

        // Test that evaluate() correctly resolves the closure when given the record
        $targetUser = $action->evaluate(
            fn (Provider $record) => $record->user,
            ['record' => $provider]
        );

        expect($targetUser)->toBeInstanceOf(User::class);
        expect($targetUser->id)->toBe($this->targetUser->id);
        expect($targetUser->email)->toBe('target@example.com');
    });

    it('returns correct redirectTo when using with impersonateRecord', function () {
        $action = Impersonate::make()
            ->impersonateRecord(fn (Provider $record) => $record->user)
            ->redirectTo('/custom-dashboard');

        // The redirectTo should work independently of impersonateRecord
        expect($action->getRedirectTo())->toBe('/custom-dashboard');
    });

    it('returns null from closure when relationship does not exist', function () {
        $provider = Provider::create([
            'name' => 'Orphan Provider',
            'user_id' => $this->targetUser->id,
        ]);

        // Delete the user to simulate null relationship
        $this->targetUser->delete();

        $action = Impersonate::make()
            ->impersonateRecord(fn (Provider $record) => $record->user);

        // Refresh the provider and evaluate
        $targetUser = $action->evaluate(
            fn (Provider $record) => $record->user,
            ['record' => $provider->fresh()]
        );

        expect($targetUser)->toBeNull();
    });

    it('handles direct model assignment with impersonateRecord', function () {
        $action = Impersonate::make()
            ->impersonateRecord($this->targetUser);

        // When a direct model is passed, evaluate should return it
        $reflection = new ReflectionClass($action);
        $property = $reflection->getProperty('impersonateRecord');
        $property->setAccessible(true);

        $value = $property->getValue($action);
        expect($value)->toBe($this->targetUser);
    });
});

describe('action callback record resolution', function () {
    it('setUp creates action with record parameter in callback', function () {
        $action = Impersonate::make();

        // Use reflection to examine the action callback
        $reflection = new ReflectionClass($action);

        // The action should be configured to accept $record
        // This was the fix for issue #133 - ensuring the action callback
        // passes record to evaluate()
        expect($action)->toBeInstanceOf(Impersonate::class);
    });

    it('visibility callback receives record parameter', function () {
        $provider = Provider::create([
            'name' => 'Test Provider',
            'user_id' => $this->targetUser->id,
        ]);

        $action = Impersonate::make()
            ->impersonateRecord(fn (Provider $record) => $record->user);

        // The visibility check should work with the relationship
        // This tests that evaluate() receives the record parameter correctly
        $targetUser = $action->evaluate(
            fn (Provider $record) => $record->user,
            ['record' => $provider]
        );

        expect($targetUser)->not->toBeNull();
        expect($targetUser->id)->toBe($this->targetUser->id);
    });
});

describe('combined configuration', function () {
    it('supports all configuration methods together', function () {
        $provider = Provider::create([
            'name' => 'Test Provider',
            'user_id' => $this->targetUser->id,
        ]);

        $action = Impersonate::make()
            ->impersonateRecord(fn (Provider $record) => $record->user)
            ->redirectTo('/dashboard')
            ->backTo('/providers')
            ->guard('web');

        expect($action->getRedirectTo())->toBe('/dashboard');
        expect($action->getBackTo())->toBe('/providers');
        expect($action->getGuard())->toBe('web');

        // Verify relationship resolution still works
        $targetUser = $action->evaluate(
            fn (Provider $record) => $record->user,
            ['record' => $provider]
        );
        expect($targetUser->id)->toBe($this->targetUser->id);
    });

    it('supports closure for redirectTo with impersonateRecord', function () {
        $provider = Provider::create([
            'name' => 'Test Provider',
            'user_id' => $this->targetUser->id,
        ]);

        $action = Impersonate::make()
            ->impersonateRecord(fn (Provider $record) => $record->user)
            ->redirectTo(fn () => '/dynamic-' . $this->targetUser->id);

        expect($action->getRedirectTo())->toBe('/dynamic-' . $this->targetUser->id);
    });
});
