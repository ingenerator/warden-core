<?php
/**
 * @author    Andrew Coulton <andrew@ingenerator.com>
 * @licence   proprietary
 */

namespace Ingenerator\Warden\Core\Notification;


interface UserNotificationMailer
{

    public function send(UserNotification $notification);
}
