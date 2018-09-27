<?php
/**
 * @author    Andrew Coulton <andrew@ingenerator.com>
 * @licence   proprietary
 */

namespace Ingenerator\Warden\Core\RateLimit;


class UndefinedRequestTypeException extends \InvalidArgumentException
{

    public function __construct(
        $type,
        $code = 0,
        \Throwable $previous = NULL
    ) {
        parent::__construct('Undefined rate limit request type `'.$type.'`', $code, $previous);
    }
}
