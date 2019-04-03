<?php
/**
 * @author    Andrew Coulton <andrew@ingenerator.com>
 * @licence   proprietary
 */

namespace Ingenerator\Warden\Core\Repository;


class DuplicateUserException extends \RuntimeException
{
    /**
     * @var string
     */
    protected $email;

    /**
     * @return string
     */
    public function getEmail()
    {
        return $this->email;
    }

    public static function forEmail($email)
    {
        $e        = new static(\sprintf('A user account with email "%s" already exists', $email));
        $e->email = $email;

        return $e;
    }

}
