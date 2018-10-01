<?php
/**
 * @author    Andrew Coulton <andrew@ingenerator.com>
 * @licence   proprietary
 */

namespace Ingenerator\Warden\Core\Config;


class Configuration
{

    /**
     * @var array
     */
    protected $config = [
        'classmap'     => [
            'entity' => [
                'user' => 'Ingenerator\Warden\Core\Entity\SimpleUser',
            ],
            'interactor_request' => [
                'change_email'       => 'Ingenerator\Warden\Core\Interactor\ChangeEmailRequest',
                'email_verification' => 'Ingenerator\Warden\Core\Interactor\EmailVerificationRequest',
                'login'              => 'Ingenerator\Warden\Core\Interactor\LoginRequest',
                'password_reset'     => 'Ingenerator\Warden\Core\Interactor\PasswordResetRequest',
                'user_registration'  => 'Ingenerator\Warden\Core\Interactor\UserRegistrationRequest',
            ],
        ],
        'registration' => [
            'require_confirmed_email' => TRUE,
        ],
    ];

    public function __construct(array $config_overrides)
    {
        $this->config = $this->mergeHashValues($this->config, $config_overrides);
    }

    protected function mergeHashValues(array $default, array $new)
    {
        foreach ($new as $key => $value) {
            if (is_array($value)) {
                $default[$key] = $this->mergeHashValues($default[$key], $value);
            } else {
                $default[$key] = $value;
            }
        }

        return $default;
    }

    /**
     * @param string $group
     * @param string $class_alias
     *
     * @return string
     */
    public function getClassName($group, $class_alias)
    {
        if ( ! isset($this->config['classmap'][$group][$class_alias])) {
            throw new \InvalidArgumentException("No classmap entry for $group.$class_alias");
        }

        $fqcn = $this->config['classmap'][$group][$class_alias];
        if ( ! class_exists($fqcn)) {
            throw new \InvalidArgumentException("Class $fqcn mapped for $group.$class_alias is not defined");
        }

        return $fqcn;
    }

    /**
     * @return boolean
     */
    public function isEmailConfirmationRequiredToRegister()
    {
        return $this->config['registration']['require_confirmed_email'];
    }

}
