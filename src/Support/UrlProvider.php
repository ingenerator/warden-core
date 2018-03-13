<?php
/**
 * @author    Andrew Coulton <andrew@ingenerator.com>
 * @licence   proprietary
 */

namespace Ingenerator\Warden\Core\Support;


use Ingenerator\Warden\Core\Entity\User;

interface UrlProvider
{
    public function getLoginUrl($email = NULL);

    public function getCompletePasswordResetUrl(array $params);

    public function getRegisterVerifyEmailUrl($email = NULL);

    public function getCompleteRegistrationUrl(array $params);

    public function getDefaultUserHomeUrl(User $user);

    public function getAfterLoginUrl(User $user);

    public function getAfterLogoutUrl();

    public function getAfterVerifyEmailSentUrl();

}
