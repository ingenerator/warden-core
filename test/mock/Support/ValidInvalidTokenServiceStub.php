<?php
/**
 * @author    Andrew Coulton <andrew@ingenerator.com>
 * @licence   proprietary
 */

namespace test\mock\Ingenerator\Warden\Core\Support;


use Ingenerator\Warden\Core\Support\EmailConfirmationTokenService;

class ValidInvalidTokenServiceStub implements EmailConfirmationTokenService
{
    public function __construct()
    {
    }

    public function createToken($params)
    {
        return 'valid';
    }

    public function isValid($token, $params)
    {
        return $token === 'valid';
    }

}
