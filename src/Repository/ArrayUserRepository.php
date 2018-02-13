<?php
/**
 * @author    Andrew Coulton <andrew@ingenerator.com>
 * @licence   proprietary
 */

namespace Ingenerator\Warden\Core\Repository;


use Ingenerator\Warden\Core\Config\Configuration;
use Ingenerator\Warden\Core\Entity\User;

class ArrayUserRepository implements UserRepository
{
    protected $users = [];

    /**
     * @var Configuration
     */
    protected $config;

    public function __construct(Configuration $config = NULL, \ArrayObject $storage = NULL)
    {
        $this->users  = $storage ?: new \ArrayObject;
        $this->config = $config ?: new Configuration([]);
    }

    /**
     * {@inheritdoc}
     */
    public function loadByEmail($email)
    {
        foreach ($this->users as $user) {
            if ($user->getEmail() === $email) {
                return $user;
            }
        }

        return NULL;
    }

    /**
     * {@inheritdoc}
     */
    public function loadById($id)
    {
        if (isset($this->users[$id])) {
            return $this->users[$id];
        } else {
            return NULL;
        }
    }

    /**
     * @return User
     */
    public function newUser()
    {
        $class = $this->config->getClassName('entity', 'user');

        return new $class;
    }

    /**
     * {@inheritdoc}
     */
    public function save(User $user)
    {
        if ($user->getId() === NULL) {
            if ($this->loadByEmail($user->getEmail())) {

            }
            $this->allocateUserId($user);
        }

        if ( ! $this->isEmailUnique($user)) {
            throw DuplicateUserException::forEmail($user->getEmail());
        }

        $this->users[$user->getId()] = $user;
    }

    /**
     * @param User $user
     */
    protected function allocateUserId(User $user)
    {
        $refl = new \ReflectionClass($user);
        $id   = $refl->getProperty('id');
        $id->setAccessible(TRUE);
        $id->setValue($user, uniqid());
    }

    /**
     * @param User $user
     *
     * @return bool
     */
    protected function isEmailUnique(User $user)
    {
        if ( ! $other_user = $this->loadByEmail($user->getEmail())) {
            return TRUE;
        }

        if ($other_user->getId() === $user->getId()) {
            return TRUE;
        }

        return FALSE;
    }

}
