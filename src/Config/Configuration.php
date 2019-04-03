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
            'entity'             => [
                'user' => \Ingenerator\Warden\Core\Entity\SimpleUser::class,
            ],
            'interactor_request' => [
                'activate_account'   => \Ingenerator\Warden\Core\Interactor\ActivateAccountRequest::class,
                'change_email'       => \Ingenerator\Warden\Core\Interactor\ChangeEmailRequest::class,
                'change_password'    => \Ingenerator\Warden\Core\Interactor\ChangePasswordRequest::class,
                'email_verification' => \Ingenerator\Warden\Core\Interactor\EmailVerificationRequest::class,
                'login'              => \Ingenerator\Warden\Core\Interactor\LoginRequest::class,
                'password_reset'     => \Ingenerator\Warden\Core\Interactor\PasswordResetRequest::class,
                'user_registration'  => \Ingenerator\Warden\Core\Interactor\UserRegistrationRequest::class,
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
            if (\is_array($value)) {
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
        if ( ! \class_exists($fqcn)) {
            throw new \InvalidArgumentException(
                "Class $fqcn mapped for $group.$class_alias is not defined"
            );
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
