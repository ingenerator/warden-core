<?php
/**
 * @author    Andrew Coulton <andrew@ingenerator.com>
 * @licence   proprietary
 */

namespace Ingenerator\Warden\Core\Interactor;

use Symfony\Component\Validator\Constraints as Assert;

class ActivateAccountRequest extends AbstractRequest implements TokenSignedRequest
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
    public function getToken()
    {
        return $this->token;
    }

}
