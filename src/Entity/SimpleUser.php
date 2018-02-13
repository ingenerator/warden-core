<?php

namespace Ingenerator\Warden\Core\Entity;

/**
 * @author    Andrew Coulton <andrew@ingenerator.com>
 * @licence   proprietary
 */
class SimpleUser implements User
{
    protected $id;
    protected $email;
    protected $password_hash;
    protected $is_active = FALSE;

    public function getEmail()
    {
        return $this->email;
    }

    public function setEmail($email)
    {
        $this->email = $email;
    }

    public function getId()
    {
        return $this->id;
    }

    public function getPasswordHash()
    {
        return $this->password_hash;
    }

    public function setPasswordHash($password_hash)
    {
        $this->password_hash = $password_hash;
    }

    public function isActive()
    {
        return $this->is_active;
    }

    public function setActive($active)
    {
        $this->is_active = $active;
    }

}
