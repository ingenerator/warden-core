<?php

namespace Ingenerator\Warden\Core\Interactor\Guard;

use Ingenerator\Warden\Core\Entity\User;
use Ingenerator\Warden\Core\Interactor\LoginRequest;
use Ingenerator\Warden\Core\Interactor\LoginResponse;

interface BeforeSuccessfulLoginGuard
{

    /**
     * Extension point for blocking login requests that would otherwise be accepted.
     *
     * Called after verifying that the user has the correct password, is active, etc. Return true
     * to allow the login to continue, or a LoginResponse if the login should be blocked.
     *
     * Note that this is not called if the user authenticates through initial registration,
     * password reset or activating their account.
     */
    public function guardSuccessfulLogin(LoginRequest $request, User $user): true|LoginResponse;

}
