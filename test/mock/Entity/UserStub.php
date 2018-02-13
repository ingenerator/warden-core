<?php
/**
 * @author    Andrew Coulton <andrew@ingenerator.com>
 * @licence   proprietary
 */

namespace test\mock\Ingenerator\Warden\Core\Entity;


use Ingenerator\Warden\Core\Entity\SimpleUser;

class UserStub extends SimpleUser
{


    public static function withEmail($email)
    {
        $instance        = new static;
        $instance->email = $email;

        return $instance;
    }

    public static function withId($id)
    {
        $instance     = new static;
        $instance->id = $id;

        return $instance;
    }

    public static function activeWithPasswordHash($email, $password_hash)
    {
        $instance                = new static;
        $instance->email         = $email;
        $instance->password_hash = $password_hash;
        $instance->is_active     = TRUE;

        return $instance;
    }

    public static function inactiveWithPasswordHash($email, $password_hash)
    {
        $instance                = new static;
        $instance->email         = $email;
        $instance->password_hash = $password_hash;
        $instance->is_active     = FALSE;

        return $instance;
    }

}
