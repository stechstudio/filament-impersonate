<?php

use Illuminate\Support\Facades\Route;
use STS\FilamentImpersonate\Facades\Impersonation;

Route::get('filament-impersonate/leave', function () {
    if (! Impersonation::isImpersonating()) {
        return redirect('/');
    }

    Impersonation::leave();

    return redirect(
        session()->pull('impersonate.back_to') ?? '/'
    );
})
    ->when(
        config('filament-impersonate.route_prefix'),
        fn (Illuminate\Routing\Route $route) => $route->prefix(config('filament-impersonate.route_prefix'))
    )
    ->name('filament-impersonate.leave')
    ->middleware(config('filament-impersonate.leave_middleware'));
