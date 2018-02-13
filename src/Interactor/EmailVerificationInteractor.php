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
     * @var Validator
     */
    protected $validator;
    /**
     * @var UserNotificationMailer
     */
    protected $mailer;
    /**
     * @var EmailConfirmationTokenService
     */
    protected $email_token_service;
    /**
     * @var UrlProvider
     */
    protected $url_provider;
    /**
     * @var UserRepository
     */
    protected $user_repository;

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

        $params = [
            'action' => $request->getAction(),
            'email'  => $request->getEmail(),
            'token'  => [
                'action' => $request->getAction(),
                'email'  => $request->getEmail(),
            ],
        ];

        if ($request->isAction(EmailVerificationRequest::REGISTER)) {
            $url = $this->buildContinuationUrl($this->url_provider->getRegistrationUrl(), $params);
        } elseif ($request->isAction(EmailVerificationRequest::RESET_PASSWORD)) {
            $params['token']['current_pw_hash'] = $request->getCurrentValue();

            $url = $this->buildContinuationUrl($this->url_provider->getLoginUrl(), $params);
        } else {
            throw new \InvalidArgumentException('Unknown request type '.$request->getAction());
        }

        $this->mailer->send(new ConfirmationRequiredNotification($request->getEmail(), $request->getAction(), $url));

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
     * @param string $base_url
     * @param array  $params
     *
     * @return string
     */
    protected function buildContinuationUrl($base_url, array $params)
    {
        $params['token'] = $this->email_token_service->createToken($params['token']);
        $url             = $base_url.'?'.http_build_query($params);

        return $url;
    }
}
