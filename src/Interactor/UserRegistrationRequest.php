<?php
/**
 * @author    Andrew Coulton <andrew@ingenerator.com>
 * @licence   proprietary
 */

namespace Ingenerator\Warden\Core\Interactor;


use Ingenerator\Warden\Core\Entity\User;
use Symfony\Component\Validator\Constraints as Assert;

class UserRegistrationRequest extends AbstractRequest
{
    /**
     * @Assert\NotBlank
     * @Assert\Email(checkMX = true)
     * @var string
     */
    protected $email;

    /**
     * @var string
     */
    protected $email_confirmation_token;

    /**
     * @Assert\Length(min = 8)
     * @Assert\NotBlank
     * @var string
     */
    protected $password;

    /**
     * @return string
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * @return string
     */
    public function getEmailConfirmationToken()
    {
        return $this->email_confirmation_token;
    }

    /**
     * @return string
     */
    public function getPassword()
    {
        return $this->password;
    }

    /**
     * @param User $user
     */
    public function populateExtraFields(User $user)
    {
        // Nothing to do here
    }

}
