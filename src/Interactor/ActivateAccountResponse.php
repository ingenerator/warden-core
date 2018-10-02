<?php
/**
 * @author    Andrew Coulton <andrew@ingenerator.com>
 * @licence   proprietary
 */

namespace Ingenerator\Warden\Core\Interactor;


class ActivateAccountResponse extends AbstractResponse
{
    const ERROR_TOKEN_INVALID = 'token-invalid';

    /**
     * @param string $new_email
     *
     * @return \Ingenerator\Warden\Core\Interactor\ActivateAccountResponse
     */
    public static function invalidToken()
    {
        return new static(FALSE, static::ERROR_TOKEN_INVALID);
    }

    /**
     * @return \Ingenerator\Warden\Core\Interactor\ActivateAccountResponse
     */
    public static function success()
    {
        return new static(TRUE, NULL);
    }

}
