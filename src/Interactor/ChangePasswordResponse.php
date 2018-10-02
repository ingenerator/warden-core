<?php
/**
 * @author    Andrew Coulton <andrew@ingenerator.com>
 * @licence   proprietary
 */

namespace Ingenerator\Warden\Core\Interactor;


use Ingenerator\Warden\Core\Entity\User;

class ChangePasswordResponse extends AbstractResponse
{
    /**
     * @var \Ingenerator\Warden\Core\Entity\User
     */
    protected $user;

    public static function success(User $user)
    {
        $instance       = new static(TRUE, NULL);
        $instance->user = $user;

        return $instance;
    }

    /**
     * @return User
     */
    public function getUser()
    {
        return $this->user;
    }

}
