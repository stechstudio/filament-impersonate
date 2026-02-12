<?php

use STS\FilamentImpersonate\Facades\Impersonation;
use STS\FilamentImpersonate\Tests\User;

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

describe('Impersonation facade', function () {
    it('resolves from container and reports impersonation state', function () {
        expect(Impersonation::isImpersonating())->toBeFalse();

        Impersonation::enter($this->admin, $this->targetUser, 'web');

        expect(Impersonation::isImpersonating())->toBeTrue();
    });

    it('can leave impersonation through the facade', function () {
        Impersonation::enter($this->admin, $this->targetUser, 'web');

        expect(auth()->id())->toBe($this->targetUser->id);

        expect(Impersonation::leave())->toBeTrue();
        expect(auth()->id())->toBe($this->admin->id);
    });
});
