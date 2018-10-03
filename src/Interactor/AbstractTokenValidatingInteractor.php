<?php
/**
 * @author    Andrew Coulton <andrew@ingenerator.com>
 * @licence   proprietary
 */

namespace Ingenerator\Warden\Core\Interactor;

use Ingenerator\Warden\Core\Support\EmailConfirmationTokenService;

abstract class AbstractTokenValidatingInteractor
{

    /**
     * @var \Ingenerator\Warden\Core\Support\EmailConfirmationTokenService
     */
    protected $email_token_service;

    public function __construct(EmailConfirmationTokenService $email_token_service)
    {
        $this->email_token_service = $email_token_service;
    }


    /**
     * @param EmailVerificationRequest $expected_verification
     * @param TokenSignedRequest       $current_request
     *
     * @return bool
     */
    protected function isTokenValid(
        EmailVerificationRequest $expected_verification,
        TokenSignedRequest $current_request
    ) {
        $params = $expected_verification->getUrlParamsToSign();

        return $this->email_token_service->isValid($current_request->getToken(), $params['token']);
    }
}
