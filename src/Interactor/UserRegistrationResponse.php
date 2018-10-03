<?php
/**
 * @author    Andrew Coulton <andrew@ingenerator.com>
 * @licence   proprietary
 */

namespace Ingenerator\Warden\Core\Interactor;


use Ingenerator\Warden\Core\Entity\User;

class UserRegistrationResponse extends AbstractResponse
{
    const ERROR_ALREADY_REGISTERED = 'already-registered';
    const ERROR_EMAIL_CONFIRMATION_INVALID = 'email-confirm-invalid';
    const ERROR_EMAIL_UNCONFIRMED = 'email-confirm-required';

    /**
     * @var User
     */
    protected $user;

    /**
     * @var string
     */
    protected $email;

    public static function badEmailConfirmation($email)
    {
        $i        = new static(FALSE, static::ERROR_EMAIL_CONFIRMATION_INVALID);
        $i->email = $email;
        
        return $i;
    }

    public static function emailConfirmationRequired()
    {
        return new static(FALSE, static::ERROR_EMAIL_UNCONFIRMED);
    }

    public static function duplicateUserEmail($email)
    {
        $instance        = new static(FALSE, static::ERROR_ALREADY_REGISTERED);
        $instance->email = $email;
        return $instance;
    }

    public static function success(User $user)
    {
        $response       = new static(TRUE);
        $response->user = $user;

        return $response;
    }

    /**
     * @return string
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * @return User
     */
    public function getUser()
    {
        if ( ! $this->wasSuccessful()) {
            throw new \BadMethodCallException('Cannot access user from unsuccessful '.__CLASS__);
        }

        return $this->user;
    }

}
