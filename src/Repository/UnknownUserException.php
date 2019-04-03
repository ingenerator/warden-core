<?php
/**
 * @author    Andrew Coulton <andrew@ingenerator.com>
 * @licence   proprietary
 */

namespace Ingenerator\Warden\Core\Repository;


class UnknownUserException extends \RuntimeException
{

    public static function forId($id)
    {
        $e = new static(\sprintf('There is no user account with id %s', $id));

        return $e;
    }
}
