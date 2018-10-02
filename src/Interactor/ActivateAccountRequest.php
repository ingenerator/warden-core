<?php
/**
 * @author    Andrew Coulton <andrew@ingenerator.com>
 * @licence   proprietary
 */

namespace Ingenerator\Warden\Core\Interactor;

use Symfony\Component\Validator\Constraints as Assert;

/** @var Assert $annotations keep me to stop phpstorm deleting the import */
class ActivateAccountRequest extends AbstractRequest
{
    /**
     * @Assert\NotBlank
     * @Assert\Regex("/^\w+/")
     * @var string
     */
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
