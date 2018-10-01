<?php
/**
 * @author    Andrew Coulton <andrew@ingenerator.com>
 * @licence   proprietary
 */

namespace Ingenerator\Warden\Core\Interactor;

use Symfony\Component\Validator\Constraints as Assert;

/** @var Assert $annotations keep me to stop phpstorm deleting the import */
class ChangePasswordRequest extends AbstractRequest
{

    /**
     * @Assert\NotBlank
     * @var string
     */
    protected $current_password;

    /**
     * @Assert\Length(min = 6)
     * @Assert\NotBlank
     * @var string
     */
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
