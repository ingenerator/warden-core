<?php
/**
 * @author    Andrew Coulton <andrew@ingenerator.com>
 * @licence   proprietary
 */

namespace test\unit\Ingenerator\Warden\Core\UserSession;

use BadMethodCallException;
use Ingenerator\Warden\Core\Entity\SimpleUser;
use Ingenerator\Warden\Core\UserSession\UserSession;
use test\mock\Ingenerator\Warden\Core\Entity\UserStub;

abstract class UserSessionTest extends \PHPUnit\Framework\TestCase
{

    public function test_it_is_initialisable()
    {
        $this->assertInstanceOf('Ingenerator\Warden\Core\UserSession\UserSession', $this->newSubject());
    }

    public function test_it_is_not_authenticated_by_default()
    {
        $this->assertFalse($this->newSubject()->isAuthenticated());
    }

    public function test_it_throws_when_attempting_to_access_user_if_not_authenticated()
    {
        $this->expectException(BadMethodCallException::class);
        $this->newSubject()->getUser();
    }

    public function test_it_is_authenticated_after_login()
    {
        $subject = $this->newSubject();
        $user    = UserStub::withId(198);
        $subject->login($user);
        $this->assertTrue($subject->isAuthenticated());
        $this->assertSame($user, $subject->getUser());
    }

    public function test_it_is_not_authenticated_after_logout()
    {
        $subject = $this->newSubject();
        $subject->login(new SimpleUser);
        $subject->logout();
        $this->assertFalse($subject->isAuthenticated());
    }

    /**
     * @return UserSession
     */
    abstract protected function newSubject();

}
