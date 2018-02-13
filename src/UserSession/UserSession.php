<?php
namespace Ingenerator\Warden\Core\UserSession;

use Ingenerator\Warden\Core\Entity\User;

/**
 * @author    Andrew Coulton <andrew@ingenerator.com>
 * @licence   proprietary
 */
interface UserSession
{

    /**
     * @param User $user
     *
     * @return void
     */
    public function login(User $user);

    /**
     * @return void
     */
    public function logout();

    /**
     * @return bool
     */
    public function isAuthenticated();

    /**
     * @return User
     */
    public function getUser();
    
}
