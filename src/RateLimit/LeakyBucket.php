<?php
/**
 * @author    Andrew Coulton <andrew@ingenerator.com>
 * @licence   proprietary
 */

namespace Ingenerator\Warden\Core\RateLimit;


interface LeakyBucket
{
    /**
     * @param string $request_type
     * @param string $requester_id
     *
     * @return LeakyBucketStatus
     */
    public function attemptRequest($request_type, $requester_id);
}
