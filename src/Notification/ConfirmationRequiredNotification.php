<?php
/**
 * @author    Andrew Coulton <andrew@ingenerator.com>
 * @licence   proprietary
 */

namespace Ingenerator\Warden\Core\Notification;


use Ingenerator\Warden\Core\Entity\User;
use Ingenerator\Warden\Core\Interactor\EmailVerificationRequest;

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
     * @var \Ingenerator\Warden\Core\Entity\User
     */
    protected $recipient_user;

    /**
     * @var EmailVerificationRequest
     */
    protected EmailVerificationRequest $initiating_request;

    /**
     * @deprecated use ::createWithRequest
     * @param string $recipient_email
     * @param string $action
     * @param string $continuation_url
     */
    public function __construct(
        $recipient_email,
        $action,
        $continuation_url,
        User $recipient_user = NULL
    ) {
        parent::__construct($recipient_email);
        $this->action = $action;
        $this->continuation_url = $continuation_url;
        $this->recipient_user = $recipient_user;
    }

    public static function createWithRequest(EmailVerificationRequest $request, string $url): ConfirmationRequiredNotification
    {
        $self = new static(
            $request->getEmail(),
            $request->getEmailAction(),
            $url,
            $request->getUser()
        );

        $self->initiating_request = $request;

        return $self;
    }

    public function getInitiatingRequest(): EmailVerificationRequest
    {
        return $this->initiating_request;
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

    /**
     * @return \Ingenerator\Warden\Core\Entity\User
     * @throws \LogicException if there is no user
     */
    public function getRecipientUser()
    {
        if ( ! $this->recipient_user instanceof User) {
            throw new \LogicException(
                'Cannot access non-initialized recipient_user - test ::hasRecipientUser()'
            );
        }

        return $this->recipient_user;
    }

    public function hasRecipientUser()
    {
        return (bool) $this->recipient_user;
    }

}
