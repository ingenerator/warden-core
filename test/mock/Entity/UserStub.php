<?php
/**
 * @author    Andrew Coulton <andrew@ingenerator.com>
 * @licence   proprietary
 */

namespace test\mock\Ingenerator\Warden\Core\Entity;


use Ingenerator\Warden\Core\Entity\SimpleUser;

class UserStub extends SimpleUser
{

    /**
     * @param array $params
     *
     * @return \test\mock\Ingenerator\Warden\Core\Entity\UserStub
     */
    public static function fromArray(array $params)
    {
        $i = new static;
        foreach ($params as $prop => $value) {
            if ( ! property_exists($i, $prop)) {
                throw new \InvalidArgumentException(
                    'Undefined property '.$prop.' on '.get_class($i)
                );
            }
            $i->$prop = $value;
        }
        return $i;
    }

    public static function withEmail($email)
    {
        return static::fromArray(['email' => $email]);
    }

    public static function withId($id)
    {
        return static::fromArray(['id' => $id]);
    }

    public static function activeWithPasswordHash($email, $password_hash)
    {
        return static::fromArray(
            [
                'email'         => $email,
                'password_hash' => $password_hash,
                'is_active'     => TRUE
            ]
        );
    }

    public static function inactiveWithPasswordHash($email, $password_hash)
    {
        return static::fromArray(
            [
                'email'         => $email,
                'password_hash' => $password_hash,
                'is_active'     => FALSE
            ]
        );
    }

}
