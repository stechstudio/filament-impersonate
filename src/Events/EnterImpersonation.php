<?php

namespace STS\FilamentImpersonate\Events;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class EnterImpersonation
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Authenticatable $impersonator,
        public Authenticatable $impersonated,
    ) {}
}
