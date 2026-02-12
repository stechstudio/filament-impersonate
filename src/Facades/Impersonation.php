<?php

namespace STS\FilamentImpersonate\Facades;

use Illuminate\Support\Facades\Facade;
use STS\FilamentImpersonate\Services\ImpersonateManager;

/**
 * @method static bool isImpersonating()
 * @method static bool enter(\Illuminate\Contracts\Auth\Authenticatable $from, \Illuminate\Contracts\Auth\Authenticatable $to, ?string $guardName = null)
 * @method static bool leave()
 * @method static void clear()
 * @method static int|string|null getImpersonatorId()
 * @method static \Illuminate\Contracts\Auth\Authenticatable|null getImpersonator()
 * @method static string|null getImpersonatorGuardName()
 * @method static string|null getImpersonatorGuardUsingName()
 *
 * @see \STS\FilamentImpersonate\Services\ImpersonateManager
 */
class Impersonation extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return ImpersonateManager::class;
    }
}
