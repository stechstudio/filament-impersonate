<?php

use Filament\Facades\Filament;
use Illuminate\Support\Facades\Route;
use Lab404\Impersonate\Services\ImpersonateManager;
use STS\FilamentImpersonate\Events\LeaveImpersonate;

Route::get('filament-impersonate/leave', function () {
    if (! app(ImpersonateManager::class)->isImpersonating()) {
        return redirect('/');
    }

    event(new LeaveImpersonate(
        Filament::auth()->user()
    ));

    app(ImpersonateManager::class)->leave();

    return redirect(
        session()->pull('impersonate.back_to') ?? config('filament.path')
    );
})->name('filament-impersonate.leave')->middleware(config('filament-impersonate.leave_middleware'));