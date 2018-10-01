<?php
/**
 * @author    Andrew Coulton <andrew@ingenerator.com>
 * @licence   proprietary
 */

namespace Ingenerator\Warden\Core\Interactor;


class ChangeEmailResponse extends AbstractResponse
{
    const ERROR_TOKEN_INVALID = 'token-invalid';
    const ERROR_UNKNOWN_USER = 'unknown-user';
    const ERROR_ALREADY_REGISTERED = 'already-registered';

    /**
     * @var string
     */
    protected $new_email;

    /**
     * @param string $new_email
     *
     * @return \Ingenerator\Warden\Core\Interactor\ChangeEmailResponse
     */
    public static function duplicateUserEmail($new_email)
    {
        $instance            = new static(FALSE, static::ERROR_ALREADY_REGISTERED);
        $instance->new_email = $new_email;
        return $instance;
    }

    /**
     * @param string $new_email
     *
     * @return \Ingenerator\Warden\Core\Interactor\ChangeEmailResponse
     */
    public static function invalidToken($new_email)
    {
        $instance            = new static(FALSE, static::ERROR_TOKEN_INVALID);
        $instance->new_email = $new_email;

        return $instance;
    }

    /**
     * @param string $new_email
     *
     * @return \Ingenerator\Warden\Core\Interactor\ChangeEmailResponse
     */
    public static function success($new_email)
    {
        $instance            = new static(TRUE, NULL);
        $instance->new_email = $new_email;

        return $instance;
    }

    /**
     * @param string $new_email
     *
     * @return \Ingenerator\Warden\Core\Interactor\ChangeEmailResponse
     */
    public static function unknownUser($new_email)
    {
        $instance            = new static(FALSE, static::ERROR_UNKNOWN_USER);
        $instance->new_email = $new_email;

        return $instance;
    }

    /**
     * @return string
     */
    public function getNewEmail()
    {
        return $this->new_email;
    }

}
