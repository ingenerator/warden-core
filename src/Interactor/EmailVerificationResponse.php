<?php
/**
 * @author    Andrew Coulton <andrew@ingenerator.com>
 * @licence   proprietary
 */

namespace Ingenerator\Warden\Core\Interactor;


class EmailVerificationResponse extends AbstractResponse
{

    const ERROR_ALREADY_REGISTERED = 'already-registered';
    const ERROR_RATE_LIMITED = 'rate-limited';

    /**
     * @var string
     */
    protected $email;

    /**
     * @var \DateTimeImmutable
     */
    protected $retry_after;

    public static function success($email)
    {
        $instance        = new static(TRUE, NULL);
        $instance->email = $email;
        return $instance;
    }

    public static function alreadyRegistered($email)
    {
        $instance        = new static(FALSE, static::ERROR_ALREADY_REGISTERED);
        $instance->email = $email;
        return $instance;
    }

    public static function rateLimited($email, \DateTimeImmutable $retry_after)
    {
        $instance              = new static(FALSE, static::ERROR_RATE_LIMITED);
        $instance->email       = $email;
        $instance->retry_after = $retry_after;

        return $instance;
    }

    /**
     * @return string
     */
    public function getEmail()
    {
        return $this->email;
    }

    public function canRetryAfter()
    {
        return $this->retry_after;
    }


}
