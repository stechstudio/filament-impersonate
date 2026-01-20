<?php

use Illuminate\Support\Facades\Route;
use Lab404\Impersonate\Services\ImpersonateManager;

Route::get('filament-impersonate/leave', function () {
    if (!app(ImpersonateManager::class)->isImpersonating()) {
        return redirect('/');
    }

    app(ImpersonateManager::class)->leave();

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
