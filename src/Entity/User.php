<?php

namespace Ingenerator\Warden\Core\Entity;

/**
 * @author    Andrew Coulton <andrew@ingenerator.com>
 * @licence   proprietary
 */
interface User
{

    public function getEmail();

    public function setEmail($email);

    public function getId();

    public function getPasswordHash();

    public function setPasswordHash($password_hash);

    public function isActive();

    public function setActive($active);

}
