<?php

namespace STS\FilamentImpersonate\Guard;

use Illuminate\Auth\SessionGuard as BaseSessionGuard;
use Illuminate\Contracts\Auth\Authenticatable;

class SessionGuard extends BaseSessionGuard
{
    /**
     * Login a user without firing the Login event or cycling the remember token.
     * Used during impersonation to prevent auth events from clearing session state.
     */
    public function quietLogin(Authenticatable $user): void
    {
        $this->updateSession($user->getAuthIdentifier());
        $this->setUser($user);
    }

    /**
     * Logout a user without firing the Logout event or updating the remember token.
     * Used during impersonation to prevent auth events from clearing session state.
     */
    public function quietLogout(): void
    {
        $this->clearUserDataFromStorage();
        $this->user = null;
        $this->loggedOut = true;
    }
}
