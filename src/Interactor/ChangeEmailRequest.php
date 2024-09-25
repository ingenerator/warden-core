<?php
/**
 * @author    Andrew Coulton <andrew@ingenerator.com>
 * @licence   proprietary
 */

namespace Ingenerator\Warden\Core\Interactor;

use Symfony\Component\Validator\Constraints as Assert;

class ChangeEmailRequest extends AbstractRequest implements TokenSignedRequest
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
    #[Assert\NotBlank]
    #[Assert\Email(mode: 'strict')]
    protected $email;

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
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * @return string
     */
    public function getToken()
    {
        return $this->token;
    }

}
