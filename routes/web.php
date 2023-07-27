<?php

use Filament\Facades\Filament;
use Illuminate\Support\Facades\Route;
use Lab404\Impersonate\Services\ImpersonateManager;

Route::get('filament-impersonate/leave', function() {
    if(!app(ImpersonateManager::class)->isImpersonating()) {
        return redirect('/');
    }

    app(ImpersonateManager::class)->leave();

    $panel = Filament::getPanel(session()->get('impersonate.back_to_panel'));

    return redirect(
        session()->pull('impersonate.back_to') ?? $panel->getUrl()
    );
})->name('filament-impersonate.leave')->middleware(config('filament-impersonate.leave_middleware'));
