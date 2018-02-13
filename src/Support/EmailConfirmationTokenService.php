<?php
/**
 * @author    Andrew Coulton <andrew@ingenerator.com>
 * @licence   proprietary
 */

namespace Ingenerator\Warden\Core\Support;


interface EmailConfirmationTokenService
{

    public function createToken($params);

    public function isValid($token, $params);

}
