<?php

namespace STS\FilamentImpersonate\Events;

use Illuminate\Foundation\Events\Dispatchable;

class LeaveImpersonate
{
    use Dispatchable;

    public $user;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct($user)
    {
        $this->user = $user;
    }
}