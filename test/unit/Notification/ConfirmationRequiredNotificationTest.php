<?php
/**
 * @author    Andrew Coulton <andrew@ingenerator.com>
 * @licence   proprietary
 */

namespace test\unit\Ingenerator\Warden\Notification;


use Ingenerator\Warden\Core\Notification\ConfirmationRequiredNotification;
use PHPUnit\Framework\TestCase;
use test\mock\Ingenerator\Warden\Core\Entity\UserStub;

class ConfirmationRequiredNotificationTest extends TestCase
{
    /**
     * @var string
     */
    protected $recipient_email = 'foo@bar.com';

    /**
     * @var string
     */
    protected $action = 'whatever';

    /**
     * @var string
     */
    protected $continuation_url = 'https://some.where/url';

    /**
     * @var \Ingenerator\Warden\Core\Entity\User
     */
    protected $recipient_user;

    public function test_it_is_initialisable()
    {
        $this->assertInstanceOf(ConfirmationRequiredNotification::class, $this->newSubject());
    }


    public function test_it_has_and_returns_user_if_set()
    {
        $this->recipient_user = UserStub::fromArray([]);
        $subject = $this->newSubject();
        $this->assertTrue($subject->hasRecipientUser());
        $this->assertSame($this->recipient_user, $subject->getRecipientUser());
    }

    public function test_it_has_no_user_if_unset()
    {
        $this->recipient_user = NULL;
        $this->assertSame(FALSE, $this->newSubject()->hasRecipientUser());
    }

    /**
     * @expectedException \LogicException
     */
    public function test_it_throws_if_attempt_to_get_unset_user()
    {
        $this->recipient_user = NULL;
        $this->newSubject()->getRecipientUser();
    }

    protected function newSubject()
    {
        return new ConfirmationRequiredNotification(
            $this->recipient_email,
            $this->action,
            $this->continuation_url,
            $this->recipient_user
        );
    }
}
