<?php
/**
 * @author    Andrew Coulton <andrew@ingenerator.com>
 * @licence   proprietary
 */

namespace Ingenerator\Warden\Core\Interactor;
use Symfony\Component\Validator\Constraints as Assert;

class PasswordResetRequest extends AbstractRequest implements TokenSignedRequest
{

    /**
     * @var string
     */
    #[Assert\NotBlank]
    #[Assert\Regex('/^\w+$/')]
    protected $user_id;

    /**
     * @var string
     */
    #[Assert\Length(min: 8)]
    #[Assert\NotBlank]
    protected $new_password;

    /**
     * @var string
     */
    protected $token;

    /**
     * @return string
     */
    public function getUserId()
    {
        return $this->user_id;
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
