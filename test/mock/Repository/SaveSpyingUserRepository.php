<?php
/**
 * @author    Andrew Coulton <andrew@ingenerator.com>
 * @licence   proprietary
 */

namespace test\mock\Ingenerator\Warden\Core\Repository;

use Ingenerator\Warden\Core\Entity\User;
use Ingenerator\Warden\Core\Repository\ArrayUserRepository;

class SaveSpyingUserRepository extends ArrayUserRepository
{

    /**
     * @var User[]
     */
    protected $users_saved;

    public function __construct(array $users = [])
    {
        parent::__construct();
        foreach ($users as $user) {
            $this->save($user);
        }
        $this->users_saved = [];
    }

    public function save(User $user)
    {
        parent::save($user);
        $this->users_saved[] = clone($user);
    }

    public function assertNothingSaved()
    {
        \PHPUnit\Framework\Assert::assertEmpty($this->users_saved);
    }

    public function assertOneSaved(User $user)
    {
        \PHPUnit\Framework\Assert::assertEquals([$user], $this->users_saved);
    }

}
