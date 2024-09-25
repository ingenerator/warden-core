<?php
/**
 * @author    Andrew Coulton <andrew@ingenerator.com>
 * @licence   proprietary
 */

namespace Ingenerator\Warden\Core\Interactor;

use Symfony\Component\Validator\Constraints as Assert;

class ChangePasswordRequest extends AbstractRequest
{

    /**
     * @var string
     */
    #[Assert\NotBlank]
    protected $current_password;

    /**
     * @var string
     */
    #[Assert\Length(min: 8)]
    #[Assert\NotBlank]
    protected $new_password;

    /**
     * @var \Ingenerator\Warden\Core\Entity\User
     */
    protected $user;

    /**
     * @return \Ingenerator\Warden\Core\Entity\User
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * @return string
     */
    public function getCurrentPassword()
    {
        return $this->current_password;
    }

    /**
     * @return string
     */
    public function getNewPassword()
    {
        return $this->new_password;
    }

}
