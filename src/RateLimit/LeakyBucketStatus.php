<?php
/**
 * @author    Andrew Coulton <andrew@ingenerator.com>
 * @licence   proprietary
 */

namespace Ingenerator\Warden\Core\RateLimit;


class LeakyBucketStatus
{
    /**
     * @var boolean
     */
    protected $is_rate_limited;

    /**
     * @var \DateTimeImmutable
     */
    protected $next_available_at;

    public static function rateLimited(\DateTimeImmutable $next_available)
    {
        $i                    = new static;
        $i->is_rate_limited   = TRUE;
        $i->next_available_at = $next_available;
        return $i;
    }

    /**
     * @return \Ingenerator\Warden\Core\RateLimit\LeakyBucketStatus
     */
    public static function allowed()
    {
        $i                  = new static;
        $i->is_rate_limited = FALSE;
        return $i;
    }

    /**
     * @return bool
     */
    public function isRateLimited()
    {
        return $this->is_rate_limited;
    }

    /**
     * @return \DateTimeImmutable
     */
    public function getNextAvailableTime()
    {
        return $this->next_available_at;
    }
}
