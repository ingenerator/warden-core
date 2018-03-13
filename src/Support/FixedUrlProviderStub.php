<?php
/**
 * @author    Andrew Coulton <andrew@ingenerator.com>
 */

namespace Ingenerator\Warden\Core\Support;

use Ingenerator\Warden\Core\Entity\User;

class FixedUrlProviderStub implements UrlProvider
{
    public function getAfterLoginUrl(User $user)
    {
        return '/after-login?'.http_build_query(['email' => $user->getEmail()]);
    }

    public function getAfterLogoutUrl()
    {
        return '/after-logout';
    }

    public function getAfterVerifyEmailSentUrl()
    {
        return '/after-verify-email-sent';
    }

    public function getCompletePasswordResetUrl(array $params)
    {
        return '/complete-password-reset?'.http_build_query($params);
    }

    public function getCompleteRegistrationUrl(array $params)
    {
        return '/complete-registration?'.http_build_query($params);
    }

    public function getDefaultUserHomeUrl(User $user)
    {
        return '/user-home?'.http_build_query(['email' => $user->getEmail()]);
    }

    public function getLoginUrl($email = NULL)
    {
        $url = '/login';
        if ($email) {
            $url .= '?'.http_build_query(['email' => $email]);
        }

        return $url;
    }

    public function getRegisterVerifyEmailUrl($email = NULL)
    {
        $url = '/register-verify-email';
        if ($email) {
            $url .= '?'.http_build_query(['email' => $email]);
        }

        return $url;
    }

}
