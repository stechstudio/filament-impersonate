<?php

namespace STS\FilamentImpersonate\Tests;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Lab404\Impersonate\Models\Impersonate;

class User extends Authenticatable
{
    use Impersonate;

    protected $guarded = [];

    public function canImpersonate(): bool
    {
        return true;
    }

    public function canBeImpersonated(): bool
    {
        return true;
    }
}
