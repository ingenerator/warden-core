<?php
/**
 * @author    Andrew Coulton <andrew@ingenerator.com>
 * @licence   proprietary
 */

namespace test\unit\Ingenerator\Warden\Notification;


use Ingenerator\Warden\Core\Interactor\EmailVerificationRequest;
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
    protected $continuation_url = 'https://some.where/url';

    /**
     * @var EmailVerificationRequest
     */
    protected $request;

    public function test_it_is_initialisable()
    {
        $this->assertInstanceOf(ConfirmationRequiredNotification::class, $this->newSubject());
    }


    public function test_it_has_and_returns_user_if_set()
    {
        $user = UserStub::fromArray([]);
        $this->request = EmailVerificationRequest::forPasswordReset($user);
        $subject = $this->newSubject();
        $this->assertTrue($subject->hasRecipientUser());
        $this->assertSame($user, $subject->getRecipientUser());
    }

    public function test_it_has_and_returns_initiating_request()
    {
        $subject = $this->newSubject();
        $this->assertSame($this->request, $subject->getInitiatingRequest());
    }

    public function test_it_has_no_user_if_unset()
    {
        $this->assertSame(FALSE, $this->newSubject()->hasRecipientUser());
    }

    public function test_it_throws_if_attempt_to_get_unset_user()
    {
        $this->expectException(\LogicException::class);
        $this->newSubject()->getRecipientUser();
    }

    protected function newSubject()
    {
        return ConfirmationRequiredNotification::createWithRequest($this->request, $this->continuation_url);
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->request = EmailVerificationRequest::forRegistration($this->recipient_email);
    }


}
