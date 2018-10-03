<?php
/**
 * @author    Andrew Coulton <andrew@ingenerator.com>
 * @licence   proprietary
 */

namespace Ingenerator\Warden\Core\Interactor;


interface TokenSignedRequest
{

    /**
     * @return string
     */
    public function getToken();

}
