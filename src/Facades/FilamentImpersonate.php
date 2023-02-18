<?php

namespace STS\FilamentImpersonate\Facades;

use Illuminate\Support\Facades\Facade;
use STS\FilamentImpersonate\FilamentImpersonateManager;

/**
 * @mixin FilamentImpersonateManager
 */
class FilamentImpersonate extends Facade
{
    protected static function getFacadeAccessor()
    {
        return FilamentImpersonateManager::class;
    }
}