<?php
/**
 * @author    Andrew Coulton <andrew@ingenerator.com>
 * @licence   proprietary
 */

namespace Ingenerator\Warden\Core\Notification;


abstract class UserNotification
{

    private $recipient_email;

    public function __construct($recipient_email)
    {
        $this->recipient_email = $recipient_email;
    }

    /**
     * @return mixed
     */
    public function getRecipientEmail()
    {
        return $this->recipient_email;
    }

}
