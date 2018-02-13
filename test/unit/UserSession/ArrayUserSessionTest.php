<?php
/**
 * @author    Andrew Coulton <andrew@ingenerator.com>
 * @licence   proprietary
 */

namespace test\unit\Ingenerator\Warden\Core\UserSession;


use Ingenerator\Warden\Core\UserSession\SimplePropertyUserSession;

class ArrayUserSessionTest extends UserSessionTest
{

    public function newSubject()
    {
        return new SimplePropertyUserSession;
    }

}
