<?php
/**
 * @author    Andrew Coulton <andrew@ingenerator.com>
 * @licence   proprietary
 */

namespace Ingenerator\Warden\Core\Interactor;


use Ingenerator\Warden\Core\Entity\User;

class LoginResponse extends AbstractResponse
{

    const ERROR_EMAIL_VERIFICATION_FAILED = 'email-verification-failed';
    const ERROR_NOT_ACTIVE = 'not-active';
    const ERROR_NOT_ACTIVE_ACTIVATION_THROTTLED = 'not-active-activation-throttled';
    const ERROR_NOT_REGISTERED = 'not-registered';
    const ERROR_PASSWORD_INCORRECT = 'password-incorrect';
    const ERROR_PASSWORD_INCORRECT_RESET_THROTTLED = 'password-incorrect-reset-throttled';
    const ERROR_RATE_LIMITED = 'rate-limited';

    /**
     * @var \DateTimeImmutable
     */
    protected $can_retry_after;

    /**
     * @var string
     */
    protected $email;

    /**
     * @var string
     */
    protected $failure_detail;

    /**
     * @var User
     */
    protected $user;

    public static function emailFailed(User $user)
    {
        $instance        = new static(FALSE, self::ERROR_EMAIL_VERIFICATION_FAILED);
        $instance->user  = $user;
        $instance->email = $user->getEmail();

        return $instance;
    }

    public static function notActive(User $user)
    {
        $instance        = new static(FALSE, self::ERROR_NOT_ACTIVE);
        $instance->user  = $user;
        $instance->email = $user->getEmail();

        return $instance;
    }

    public static function notActiveRateLimited(USer $user, \DateTimeImmutable $retry_after)
    {
        $instance                  = new static(FALSE, self::ERROR_NOT_ACTIVE_ACTIVATION_THROTTLED);
        $instance->user            = $user;
        $instance->email           = $user->getEmail();
        $instance->can_retry_after = $retry_after;

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

    public static function passwordIncorrectRateLimited(User $user, \DateTimeImmutable $retry_after)
    {
        $response                  = new static(
            FALSE,
            self::ERROR_PASSWORD_INCORRECT_RESET_THROTTLED
        );
        $response->user            = $user;
        $response->email           = $user->getEmail();
        $response->can_retry_after = $retry_after;

        return $response;
    }

    public static function rateLimited($email, \DateTimeImmutable $retry_after, array $full_buckets)
    {
        $response                  = new static(FALSE, self::ERROR_RATE_LIMITED);
        $response->can_retry_after = $retry_after;
        $response->email           = $email;
        $response->failure_detail  = \implode(',', $full_buckets);

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
            throw new \BadMethodCallException(
                'Cannot access user from '.__CLASS__.' with code '.$this->failure_code
            );
        }

        return $this->user;
    }

    /**
     * @return \DateTimeImmutable
     */
    public function canRetryAfter()
    {
        return $this->can_retry_after;
    }

    /**
     * @return string
     */
    public function getFailureDetail()
    {
        return $this->failure_detail;
    }
}
