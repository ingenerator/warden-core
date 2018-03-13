<?php
/**
 * @author    Andrew Coulton <andrew@ingenerator.com>
 * @licence   proprietary
 */

namespace Ingenerator\Warden\Core\Support;


use Ingenerator\Warden\Core\Entity\User;

interface UrlProvider
{
    /**
     * The URL this user will be taken to when they first complete a login,
     * either by logging in with a password or through password reset etc.
     *
     * Commonly /profile
     *
     * @param User $user
     *
     * @return string
     */
    public function getAfterLoginUrl(User $user);

    /**
     * The URL to take a user to once they have logged out
     *
     * Commonly /
     *
     * @return string
     */
    public function getAfterLogoutUrl();

    /**
     * The URL to take a user to once we have successfully sent a "verify your email"
     * link prior to registration. Note the user is not authenticated nor does it make
     * sense to go back to register etc, since they have to go to their inbox now.
     *
     * Commonly / - could also be a site-specific dedicated success page
     *
     * @return string
     */
    public function getAfterVerifyEmailSentUrl();

    /**
     * The signed URL a user will use to complete their password reset. This needs to be
     * fully qualified e.g. with scheme and host as it will be rendered into their email
     * link.
     *
     * Commonly /login/reset-password
     *
     * @param array $params the registration details and confirmation token
     *
     * @return string
     */
    public function getCompletePasswordResetUrl(array $params);

    /**
     * The signed URL a user will use to complete registration once they've verified their
     * email. This needs to be fully qualified e.g. with scheme and host as it will be
     * rendered into their email link.
     *
     * Commonly /register
     *
     * @param array $params the registration details and confirmation token
     *
     * @return string
     */
    public function getCompleteRegistrationUrl(array $params);

    /**
     * The default homepage for a user. This is generally used when for example they
     * attempt to access a login / register / reset password page when they're already
     * logged in.
     *
     * Commonly /profile
     *
     * @param User $user
     *
     * @return string
     */
    public function getDefaultUserHomeUrl(User $user);

    /**
     * The login page, optionally with an email address pre-filled (e.g. if redirecting from
     * a registration attempt that failed because the user is already registered)
     *
     * Commonly /login
     *
     * @param string $email
     *
     * @return string
     */
    public function getLoginUrl($email = NULL);

    /**
     * @return string
     */
    public function getLogoutUrl();

    /**
     * The page to enter an email address and trigger verification ahead of doing a full
     * registration. Optionally with email pre-filled e.g. after a login attempt that failed
     * because the user is already registered.
     *
     * @param string $email
     *
     * @return string
     */
    public function getRegisterVerifyEmailUrl($email = NULL);

}
