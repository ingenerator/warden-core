<?php
/**
 * @author    Andrew Coulton <andrew@ingenerator.com>
 * @licence   proprietary
 */

namespace Ingenerator\Warden\Core\Interactor;


use Ingenerator\Warden\Core\Notification\ConfirmationRequiredNotification;
use Ingenerator\Warden\Core\Notification\UserNotificationMailer;
use Ingenerator\Warden\Core\Repository\UserRepository;
use Ingenerator\Warden\Core\Support\EmailConfirmationTokenService;
use Ingenerator\Warden\Core\Support\UrlProvider;
use Ingenerator\Warden\Core\Validator\Validator;

class EmailVerificationInteractor
{
    /**
     * @var EmailConfirmationTokenService
     */
    protected $email_token_service;
    /**
     * @var UserNotificationMailer
     */
    protected $mailer;
    /**
     * @var UrlProvider
     */
    protected $url_provider;
    /**
     * @var UserRepository
     */
    protected $user_repository;
    /**
     * @var Validator
     */
    protected $validator;

    public function __construct(
        Validator $validator,
        UserRepository $user_repository,
        EmailConfirmationTokenService $email_token_service,
        UrlProvider $url_provider,
        UserNotificationMailer $mailer
    ) {
        $this->validator           = $validator;
        $this->mailer              = $mailer;
        $this->email_token_service = $email_token_service;
        $this->url_provider        = $url_provider;
        $this->user_repository     = $user_repository;
    }

    /**
     * @param EmailVerificationRequest $request
     *
     * @return EmailVerificationResponse
     */
    public function execute(EmailVerificationRequest $request)
    {
        if ($errors = $this->validator->validate($request)) {
            return EmailVerificationResponse::validationFailed($errors);
        }

        if ($request->isAction(EmailVerificationRequest::REGISTER) AND $this->isRegistered($request->getEmail())) {
            return EmailVerificationResponse::alreadyRegistered($request->getEmail());
        }

        $url = $this->buildSignedContinuationUrl($request);

        $this->mailer->send(
            new ConfirmationRequiredNotification(
                $request->getEmail(),
                $request->getEmailAction(),
                $url
            )
        );

        return EmailVerificationResponse::success($request->getEmail());
    }

    /**
     * @param string $email
     *
     * @return bool
     */
    protected function isRegistered($email)
    {
        return (bool) $this->user_repository->loadByEmail($email);
    }

    /**
     * @param EmailVerificationRequest $request
     *
     * @return mixed
     */
    protected function buildSignedContinuationUrl(EmailVerificationRequest $request)
    {
        $params       = ['email' => $request->getEmail()];
        $token_params = array_merge($params, ['action' => $request->getAction()]);

        if ($request->isAction(EmailVerificationRequest::REGISTER)) {
            $params['token'] = $this->email_token_service->createToken($token_params);

            return $this->url_provider->getCompleteRegistrationUrl($params);

        } elseif ($request->isAction(EmailVerificationRequest::RESET_PASSWORD)) {
            $token_params['current_pw_hash'] = $request->getCurrentValue();

            $params['token'] = $this->email_token_service->createToken($token_params);

            return $this->url_provider->getCompletePasswordResetUrl($params);

        } else {
            throw new \InvalidArgumentException('Unknown request type '.$request->getAction());
        }
    }

}
