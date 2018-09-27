<?php
/**
 * @author    Andrew Coulton <andrew@ingenerator.com>
 * @licence   proprietary
 */

namespace test\mock\Ingenerator\Warden\Core\Support;

use Ingenerator\Warden\Core\Notification\UserNotification;
use Ingenerator\Warden\Core\Notification\UserNotificationMailer;
use PHPUnit\Framework\Assert;

class UserNotificationMailerSpy implements UserNotificationMailer
{
    protected $sent = [];

    public function assertSentOne($notification_type, $to_email)
    {
        Assert::assertCount(1, $this->sent, 'Should have sent one email');
        $notification = $this->sent[0];
        Assert::assertInstanceOf($notification_type, $notification);
        /** @var UserNotification $notification */
        Assert::assertEquals($to_email, $notification->getRecipientEmail());

        return $notification;
    }

    public function sendWardenNotification(UserNotification $notification)
    {
        $this->sent[] = clone $notification;
    }

    /**
     * @return UserNotification
     */
    public function getFirstNotification()
    {
        return $this->sent[0];
    }

    public function assertNothingSent()
    {
        Assert::assertCount(0, $this->sent, 'Should not have sent anything');
    }

}
