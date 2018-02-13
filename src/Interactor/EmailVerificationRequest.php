<?php
/**
 * @author    Andrew Coulton <andrew@ingenerator.com>
 * @licence   proprietary
 */

namespace Ingenerator\Warden\Core\Interactor;
use Ingenerator\Warden\Core\Entity\User;
use Symfony\Component\Validator\Constraints as Assert;

class EmailVerificationRequest extends AbstractRequest
{

    const REGISTER       = 'register';
    const RESET_PASSWORD = 'reset-password';

    /**
     * @var string
     */
    protected $action;
    /**
     * @var string
     */
    protected $current_value;
    /**
     * @Assert\NotBlank
     * @Assert\Email(checkMX = true)
     * @var string
     */
    protected $email;

    public static function forPasswordReset(User $user)
    {
        return static::fromArray(
            [
                'action'        => static::RESET_PASSWORD,
                'email'         => $user->getEmail(),
                'current_value' => $user->getPasswordHash(),
            ]
        );
    }

    public static function forRegistration($email)
    {
        return static::fromArray(
            [
                'action' => static::REGISTER,
                'email'  => $email,
            ]
        );
    }

    public function getAction()
    {
        return $this->action;
    }

    /**
     * @return string
     */
    public function getCurrentValue()
    {
        return $this->current_value;
    }

    public function getEmail()
    {
        return $this->email;
    }

    /**
     * @param string $action
     *
     * @return bool
     */
    public function isAction($action)
    {
        return ($this->action === $action);
    }
}
