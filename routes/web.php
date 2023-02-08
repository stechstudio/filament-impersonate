<?php

use Illuminate\Support\Facades\Route;
use STS\FilamentImpersonate\Tables\Actions\Impersonate;

Route::domain(config('filament.domain'))
    ->middleware(config('filament-impersonate.leave_middleware') ?? config('filament.middleware.base'))
    ->prefix(config('filament.path'))
    ->name('filament-impersonate.leave')
    ->get('filament-impersonate/leave', static fn() => Impersonate::leave());
