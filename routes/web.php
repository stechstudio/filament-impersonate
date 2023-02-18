<?php
use Illuminate\Support\Facades\Route;
use STS\FilamentImpersonate\Impersonate;

Route::get('filament-impersonate/leave', fn() => (new Impersonate)->leave())
    ->name('filament-impersonate.leave')
    ->middleware(config('filament-impersonate.leave_middleware'));