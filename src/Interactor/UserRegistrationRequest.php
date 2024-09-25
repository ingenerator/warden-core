<?php
/**
 * @author    Andrew Coulton <andrew@ingenerator.com>
 * @licence   proprietary
 */

namespace Ingenerator\Warden\Core\Interactor;

use Ingenerator\Warden\Core\Entity\User;
use Symfony\Component\Validator\Constraints as Assert;

class UserRegistrationRequest extends AbstractRequest implements TokenSignedRequest
{
    /**
     * @var string
     */
    #[Assert\NotBlank]
    #[Assert\Email(mode: 'strict')]
    protected $email;

    /**
     * @var string
     */
    protected $email_confirmation_token;

    /**
     * @var string
     */
    #[Assert\Length(min: 8)]
    #[Assert\NotBlank]
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
     * @return bool
     */
    public function hasToken()
    {
        return ! empty($this->email_confirmation_token);
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
