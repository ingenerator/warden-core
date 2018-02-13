<?php
/**
 * @author    Andrew Coulton <andrew@ingenerator.com>
 * @licence   proprietary
 */
namespace test\mock\Ingenerator\Warden\Core\Support;

use Ingenerator\Warden\Core\Support\PasswordHasher;

class ReversingPassswordHasherStub implements PasswordHasher
{
    public function hash($password)
    {
        return strrev($password);
    }

    public function isCorrect($password, $hash)
    {
        return $hash === $this->hash($password);
    }

    public function needsRehash($hash)
    {
        return FALSE;
    }

}
