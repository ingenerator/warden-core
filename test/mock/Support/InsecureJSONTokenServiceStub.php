<?php
/**
 * @author    Andrew Coulton <andrew@ingenerator.com>
 * @licence   proprietary
 */
namespace test\mock\Ingenerator\Warden\Core\Support;

use Ingenerator\Warden\Core\Support\EmailConfirmationTokenService;

class InsecureJSONTokenServiceStub implements EmailConfirmationTokenService
{
    public function createToken($params)
    {
        return json_encode($params);
    }

    public function isValid($token, $params)
    {
        return $token === $this->createToken($params);
    }

}
