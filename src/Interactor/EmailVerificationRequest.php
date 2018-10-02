<?php
/**
 * @author    Andrew Coulton <andrew@ingenerator.com>
 * @licence   proprietary
 */

namespace Ingenerator\Warden\Core\Interactor;

use Ingenerator\Warden\Core\Entity\User;
use Ingenerator\Warden\Core\Support\UrlProvider;
use Symfony\Component\Validator\Constraints as Assert;

/** @var Assert $annotations keep me to stop phpstorm deleting the import */
class EmailVerificationRequest extends AbstractRequest
{

    const ACTIVATE_ACCOUNT = 'activate-account';
    const NEW_USER_INVITE = 'new-user-invite';
    const REGISTER = 'register';
    const RESET_PASSWORD = 'reset-password';
    const CHANGE_EMAIL = 'change-email';

    /**
     * @var string
     */
    protected $action;

    /**
     * @Assert\NotBlank
     * @Assert\Email(checkMX = true)
     * @var string
     */
    protected $email;

    /**
     * @var string
     */
    protected $email_action;

    /**
     * @var User
     */
    protected $user;

    /**
     * @param \Ingenerator\Warden\Core\Entity\User $user
     *
     * @return \Ingenerator\Warden\Core\Interactor\EmailVerificationRequest
     */
    public static function forActivation(User $user)
    {
        return static::fromArray(
            [
                'action'       => static::ACTIVATE_ACCOUNT,
                'email'        => $user->getEmail(),
                'email_action' => static::ACTIVATE_ACCOUNT,
                'user'         => $user,
            ]
        );
    }

    /**
     * @param \Ingenerator\Warden\Core\Entity\User $user
     * @param string                               $new_email
     *
     * @return \Ingenerator\Warden\Core\Interactor\EmailVerificationRequest
     */
    public static function forChangeEmail(User $user, $new_email)
    {
        return static::fromArray(
            [
                'action'       => static::CHANGE_EMAIL,
                'email'        => $new_email,
                'email_action' => static::CHANGE_EMAIL,
                'user'         => $user,
            ]
        );
    }

    public static function forNewUserInvite(User $user)
    {
        return static::fromArray(
            [
                'action'       => static::RESET_PASSWORD,
                'email'        => $user->getEmail(),
                'email_action' => static::NEW_USER_INVITE,
                'user'         => $user,
            ]
        );
    }

    public static function forPasswordReset(User $user)
    {
        return static::fromArray(
            [
                'action' => static::RESET_PASSWORD,
                'email'  => $user->getEmail(),
                'user'   => $user,
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

    public function getEmail()
    {
        return $this->email;
    }

    /**
     * Indicates if a different variation of the email template should be sent
     *
     * For example, a NEW_USER_INVITE triggers a password reset but may need a different
     * message template.
     *
     * @return string
     */
    public function getEmailAction()
    {
        return $this->email_action ?: $this->action;
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

    public function requiresUnregisteredEmail()
    {
        // NEW_USER_INVITE doesn't, because the user is already created and persisted when it is sent
        return (
            $this->isAction(static::CHANGE_EMAIL)
            OR $this->isAction(static::REGISTER)
        );
    }

    /**
     * @return array
     */
    public function getUrlParamsToSign()
    {
        if ($this->isAction(static::ACTIVATE_ACCOUNT)) {
            return [
                'token'   => [
                    'action'  => $this->getAction(),
                    'user_id' => $this->requireUser()->getId()
                ],
                'user_id' => $this->requireUser()->getId(),
            ];
        } elseif ($this->isAction(static::RESET_PASSWORD)) {
            return [
                'email' => $this->getEmail(),
                'token' => [
                    'email'           => $this->getEmail(),
                    'action'          => $this->getAction(),
                    'current_pw_hash' => $this->requireUser()->getPasswordHash(),
                ]
            ];
        } elseif ($this->isAction(static::CHANGE_EMAIL)) {
            return [
                'email'   => $this->getEmail(),
                'token'   => [
                    'email'         => $this->getEmail(),
                    'action'        => $this->getAction(),
                    'user_id'       => $this->requireUser()->getId(),
                    'current_email' => $this->requireUser()->getEmail(),
                ],
                'user_id' => $this->requireUser()->getId(),
            ];
        } else {
            return [
                'email' => $this->getEmail(),
                'token' => [
                    'email'  => $this->getEmail(),
                    'action' => $this->getAction(),
                ]
            ];
        }
    }

    public function getContinuationUrl(UrlProvider $urls, array $params)
    {
        switch ($this->getAction()) {
            case static::ACTIVATE_ACCOUNT:
                return $urls->getCompleteActivationUrl($params);
            case static::CHANGE_EMAIL:
                return $urls->getCompleteChangeEmailUrl($params);
            case static::NEW_USER_INVITE:
                return $urls->getCompletePasswordResetUrl($params);
            case static::RESET_PASSWORD:
                return $urls->getCompletePasswordResetUrl($params);
            case static::REGISTER:
                return $urls->getCompleteRegistrationUrl($params);
            default:
                throw new \InvalidArgumentException('Unexpected request type '.$this->getAction());
        }
    }

    /**
     * @return \Ingenerator\Warden\Core\Entity\User
     */
    protected function requireUser()
    {
        if ( ! $this->user) {
            throw new \UnexpectedValueException(
                'No User assigned to '.get_class($this).':'.$this->getAction()
            );
        }

        return $this->user;
    }

    /**
     * @return \Ingenerator\Warden\Core\Entity\User|NULL
     */
    public function getUser()
    {
        return $this->user;
    }
}
