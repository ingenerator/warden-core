<?php
/**
 * @author    Andrew Coulton <andrew@ingenerator.com>
 * @licence   proprietary
 */

namespace Ingenerator\Warden\Core\Interactor;

use Symfony\Component\Validator\Constraints as Assert;

class LoginRequest extends AbstractRequest
{

    /**
     * @var string
     */
    #[Assert\NotBlank]
    #[Assert\Email(mode: 'loose')]
    protected $email;

    /**
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
    public function getPassword()
    {
        return $this->password;
    }

}
