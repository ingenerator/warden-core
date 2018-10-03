<?php
/**
 * @author    Andrew Coulton <andrew@ingenerator.com>
 * @licence   proprietary
 */

namespace Ingenerator\Warden\Core\Interactor;


use Ingenerator\Warden\Core\Entity\User;
use Symfony\Component\Validator\Constraints as Assert;

/** @var Assert $annotations keep me to stop phpstorm deleting the import */

class UserRegistrationRequest extends AbstractRequest implements TokenSignedRequest
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
    public function getToken()
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
