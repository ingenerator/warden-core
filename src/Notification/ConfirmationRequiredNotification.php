<?php
/**
 * @author    Andrew Coulton <andrew@ingenerator.com>
 * @licence   proprietary
 */

namespace Ingenerator\Warden\Core\Notification;


class ConfirmationRequiredNotification extends UserNotification
{
    /**
     * @var string
     */
    protected $continuation_url;

    /**
     * @var string
     */
    protected $action;

    /**
     * @param string $recipient_email
     * @param string $action
     * @param string $continuation_url
     */
    public function __construct($recipient_email, $action, $continuation_url)
    {
        parent::__construct($recipient_email);
        $this->action           = $action;
        $this->continuation_url = $continuation_url;
    }

    /**
     * @return string
     */
    public function getAction()
    {
        return $this->action;
    }

    /**
     * @return string
     */
    public function getContinuationUrl()
    {
        return $this->continuation_url;
    }

}
