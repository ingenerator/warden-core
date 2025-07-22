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
     *
     * Validate with a very basic regex pattern to avoid unnecessary database lookups
     * It is not intended to be a full email validation as that is not a login concern
     */
    #[Assert\NotBlank]
    #[Assert\Regex(pattern: '/^.+\@\S+\.\S+$/', message: "This value is not a valid email address.")]
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
