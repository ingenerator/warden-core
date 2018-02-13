<?php
/**
 * @author    Andrew Coulton <andrew@ingenerator.com>
 * @licence   proprietary
 */

namespace Ingenerator\Warden\Core\Repository;


use Ingenerator\Warden\Core\Entity\User;

interface UserRepository
{

    /**
     * @param string $email
     *
     * @return User
     */
    public function loadByEmail($email);

    /**
     * @param string $id
     *
     * @return User
     */
    public function loadById($id);

    /**
     * @return User
     */
    public function newUser();

    /**
     * @param User $user
     *
     * @return void
     */
    public function save(User $user);

}
