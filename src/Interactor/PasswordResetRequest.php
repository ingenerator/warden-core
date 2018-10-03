<?php
/**
 * @author    Andrew Coulton <andrew@ingenerator.com>
 * @licence   proprietary
 */

namespace Ingenerator\Warden\Core\Interactor;
use Symfony\Component\Validator\Constraints as Assert;

/** @var Assert $annotations keep me to stop phpstorm deleting the import */

class PasswordResetRequest extends AbstractRequest implements TokenSignedRequest
{

    /**
     * @Assert\NotBlank
     * @Assert\Email(checkMX = false)
     * @var string
     */
    protected $email;

    /**
     * @Assert\Length(min = 8)
     * @Assert\NotBlank
     * @var string
     */
    protected $new_password;

    /**
     * @var string
     */
    protected $token;

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
    public function getNewPassword()
    {
        return $this->new_password;
    }

    /**
     * @return string
     */
    public function getToken()
    {
        return $this->token;
    }
    
}
