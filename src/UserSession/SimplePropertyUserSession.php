<?php
/**
 * @author    Andrew Coulton <andrew@ingenerator.com>
 * @licence   proprietary
 */

namespace Ingenerator\Warden\Core\UserSession;


use Ingenerator\Warden\Core\Entity\User;

class SimplePropertyUserSession implements UserSession
{
    protected $user;

    public function login(User $user)
    {
        $this->user = $user;
    }

    public function logout()
    {
        $this->user = NULL;
    }

    public function isAuthenticated()
    {
        return (bool) $this->user;
    }

    public function getUser()
    {
        if ( ! $this->isAuthenticated()) {
            throw new \BadMethodCallException('Cannot access user in '.__CLASS__.' when no user authenticated');
        }

        return $this->user;
    }

}
