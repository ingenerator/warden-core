<?php
/**
 * @author    Andrew Coulton <andrew@ingenerator.com>
 * @licence   proprietary
 */

namespace Ingenerator\Warden\Core\Interactor;


class EmailVerificationResponse extends AbstractResponse
{

    const ERROR_ALREADY_REGISTERED = 'already-registered';

    /**
     * @var string
     */
    protected $email;

    public static function success($email)
    {
        $instance = new static(TRUE, NULL);
        $instance->email = $email;
        return $instance;
    }

    public static function alreadyRegistered($email)
    {
        $instance = new static(FALSE, static::ERROR_ALREADY_REGISTERED);
        $instance->email = $email;
        return $instance;
    }

    /**
     * @return string
     */
    public function getEmail()
    {
        return $this->email;
    }


}
