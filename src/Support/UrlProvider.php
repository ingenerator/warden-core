<?php
/**
 * @author    Andrew Coulton <andrew@ingenerator.com>
 * @licence   proprietary
 */

namespace Ingenerator\Warden\Core\Support;


interface UrlProvider
{
    public function getLoginUrl();
    public function getRegistrationUrl();

}
