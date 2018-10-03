<?php
/**
 * @author    Andrew Coulton <andrew@ingenerator.com>
 * @licence   proprietary
 */

namespace Ingenerator\Warden\Core\Interactor;


use Ingenerator\Warden\Core\Entity\User;

class TokenValidationResult
{

    /**
     * @var bool
     */
    protected $is_valid;

    /**
     * @var User
     */
    protected $user;

    /**
     * @param \Ingenerator\Warden\Core\Entity\User $user
     * @param bool                                 $is_valid
     *
     * @return \Ingenerator\Warden\Core\Interactor\TokenValidationResult
     */
    public static function forUser(User $user, $is_valid)
    {
        $i           = new static;
        $i->user     = $user;
        $i->is_valid = $is_valid;
        return $i;
    }

    /**
     * @param $is_valid
     *
     * @return \Ingenerator\Warden\Core\Interactor\TokenValidationResult
     */
    public static function withNoUser($is_valid)
    {
        $i           = new static;
        $i->is_valid = $is_valid;
        return $i;
    }

    /**
     * @return bool
     */
    public function isValid()
    {
        return $this->is_valid;
    }

    /**
     * @return \Ingenerator\Warden\Core\Entity\User|NULL
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * @return string
     */
    public function getUserEmail()
    {
        return $this->hasUser() ? $this->getUser()->getEmail() : NULL;
    }

    /**
     * @return bool
     */
    public function hasUser()
    {
        return (bool) $this->user;
    }

}
