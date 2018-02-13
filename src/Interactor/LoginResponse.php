<?php
/**
 * @author    Andrew Coulton <andrew@ingenerator.com>
 * @licence   proprietary
 */

namespace Ingenerator\Warden\Core\Interactor;


use Ingenerator\Warden\Core\Entity\User;

class LoginResponse extends AbstractResponse
{

    const ERROR_NOT_ACTIVE         = 'not-active';
    const ERROR_NOT_REGISTERED     = 'not-registered';
    const ERROR_PASSWORD_INCORRECT = 'password-incorrect';

    /**
     * @var string
     */
    protected $email;

    /**
     * @var User
     */
    protected $user;

    public static function notActive(User $user)
    {
        $instance       = new static(FALSE, self::ERROR_NOT_ACTIVE);
        $instance->user = $user;

        return $instance;
    }

    public static function notRegistered($email)
    {
        $response        = new static(FALSE, self::ERROR_NOT_REGISTERED);
        $response->email = $email;

        return $response;
    }

    public static function passwordIncorrect(User $user)
    {
        $response        = new static(FALSE, self::ERROR_PASSWORD_INCORRECT);
        $response->user  = $user;
        $response->email = $user->getEmail();

        return $response;
    }

    public static function success(User $user)
    {
        $response        = new static(TRUE);
        $response->email = $user->getEmail();
        $response->user  = $user;

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
        if ( ! $this->user) {
            throw new \BadMethodCallException('Cannot access user from '.__CLASS__.' with code '.$this->failure_code);
        }

        return $this->user;
    }
}
