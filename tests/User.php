<?php

namespace STS\FilamentImpersonate\Tests;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    use SoftDeletes;

    protected $guarded = [];

    public static bool $canImpersonateResult = true;
    public static bool $canBeImpersonatedResult = true;
    public static bool $checkCanImpersonate = true;
    public static bool $checkCanBeImpersonated = true;

    public static function resetAuthorizationDefaults(): void
    {
        static::$canImpersonateResult = true;
        static::$canBeImpersonatedResult = true;
        static::$checkCanImpersonate = true;
        static::$checkCanBeImpersonated = true;
    }

    public function canImpersonate(): bool
    {
        return static::$canImpersonateResult;
    }

    public function canBeImpersonated(): bool
    {
        return static::$canBeImpersonatedResult;
    }
}
