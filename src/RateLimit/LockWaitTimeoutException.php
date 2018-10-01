<?php
/**
 * @author    Andrew Coulton <andrew@ingenerator.com>
 * @licence   proprietary
 */

namespace Ingenerator\Warden\Core\RateLimit;


class LockWaitTimeoutException extends \RuntimeException
{

    public function __construct($bucket_id, $timeout_ms)
    {
        parent::__construct('Could not obtain lock on bucket '.$bucket_id.' after '.$timeout_ms);
    }

}
