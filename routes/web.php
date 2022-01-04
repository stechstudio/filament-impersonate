<?php
use Illuminate\Support\Facades\Route;
use Lab404\Impersonate\Services\ImpersonateManager;

Route::get('filament-impersonate/leave', function() {
    if(!app(ImpersonateManager::class)->isImpersonating()) {
        return redirect('/');
    }

    app(ImpersonateManager::class)->leave();

    session()->forget('password_hash_' . config('filament-impersonate.guard'));
    session()->forget('password_hash_' . config('filament.auth.guard'));

    return redirect()->to(session()->pull('impersonate.back_to') ?? config('filament.path'));
})->name('filament-impersonate.leave')->middleware(config('filament-impersonate.leave_middleware'));