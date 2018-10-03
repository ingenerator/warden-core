<?php
/**
 * @author    Andrew Coulton <andrew@ingenerator.com>
 * @licence   proprietary
 */

namespace Ingenerator\Warden\Core\Interactor;


class PasswordResetResponse extends AbstractResponse
{

    const ERROR_TOKEN_INVALID = 'token-invalid';
    const ERROR_UNKNOWN_USER  = 'unknown-user';
    /**
     * @var string
     */
    protected $email;

    public static function invalidToken($email)
    {
        $instance        = new static(FALSE, static::ERROR_TOKEN_INVALID);
        $instance->email = $email;

        return $instance;
    }

    public static function success($email)
    {
        $instance        = new static(TRUE, NULL);
        $instance->email = $email;

        return $instance;
    }

    public static function unknownUser()
    {
        return new static(FALSE, static::ERROR_UNKNOWN_USER);
    }

    /**
     * @return string
     */
    public function getEmail()
    {
        return $this->email;
    }

}
