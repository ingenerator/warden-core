<?php
/**
 * @author    Andrew Coulton <andrew@ingenerator.com>
 * @licence   proprietary
 */

namespace test\integration\Ingenerator\Warden\Core\Repository;


use Ingenerator\Warden\Core\Repository\ArrayUserRepository;

class ArrayUserRepositoryTest extends UserRepositoryTest
{
    /**
     * @var \ArrayObject
     */
    protected $storage;

    public function setUp()
    {
        parent::setUp();
        $this->storage = new \ArrayObject;
    }

    protected function newSubject()
    {
        return new ArrayUserRepository(
            $this->config,
            $this->storage
        );
    }

}
