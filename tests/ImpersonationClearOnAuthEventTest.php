<?php

use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
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

it('keeps the impersonation when a login fires on an unrelated guard', function () {
    Impersonation::enter($this->admin, $this->targetUser, 'web');

    // A different guard sharing the same session (e.g. a customer/storefront
    // guard) authenticates. This must not tear down the admin impersonation.
    event(new Login('customer', $this->targetUser, false));

    expect(Impersonation::isImpersonating())->toBeTrue();
});

it('keeps the impersonation when a logout fires on an unrelated guard', function () {
    Impersonation::enter($this->admin, $this->targetUser, 'web');

    event(new Logout('customer', $this->targetUser));

    expect(Impersonation::isImpersonating())->toBeTrue();
});

it('clears the impersonation when a fresh login fires on the impersonator guard', function () {
    Impersonation::enter($this->admin, $this->targetUser, 'web');

    event(new Login('web', $this->admin, false));

    expect(Impersonation::isImpersonating())->toBeFalse();
});

it('clears the impersonation when a logout fires on the impersonator guard', function () {
    Impersonation::enter($this->admin, $this->targetUser, 'web');

    event(new Logout('web', $this->targetUser));

    expect(Impersonation::isImpersonating())->toBeFalse();
});

it('still clears on login when not impersonating', function () {
    expect(Impersonation::isImpersonating())->toBeFalse();

    event(new Login('customer', $this->targetUser, false));

    expect(Impersonation::isImpersonating())->toBeFalse();
});
