<?php
/**
 * @author    Andrew Coulton <andrew@ingenerator.com>
 * @licence   proprietary
 */

namespace Ingenerator\Warden\Core\Interactor;


use Ingenerator\Warden\Core\Notification\ConfirmationRequiredNotification;
use Ingenerator\Warden\Core\Notification\UserNotificationMailer;
use Ingenerator\Warden\Core\RateLimit\LeakyBucket;
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
     * @var \Ingenerator\Warden\Core\RateLimit\LeakyBucket
     */
    protected $leaky_bucket;

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
        LeakyBucket $leaky_bucket,
        UrlProvider $url_provider,
        UserNotificationMailer $mailer
    ) {
        $this->validator           = $validator;
        $this->mailer              = $mailer;
        $this->email_token_service = $email_token_service;
        $this->url_provider        = $url_provider;
        $this->user_repository     = $user_repository;
        $this->leaky_bucket        = $leaky_bucket;
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

        if ($request->requiresUnregisteredEmail() AND $this->isRegistered($request->getEmail())) {
            return EmailVerificationResponse::alreadyRegistered($request->getEmail());
        }

        $bucket = $this->checkRateLimit($request);
        if ($bucket->isRateLimited()) {
            return EmailVerificationResponse::rateLimited(
                $request->getEmail(),
                $bucket->getNextAvailableTime()
            );
        }

        $url = $this->buildSignedContinuationUrl($request);

        $this->mailer->sendWardenNotification(
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
        return (bool) $this->user_repository->findByEmail($email);
    }

    /**
     * @param EmailVerificationRequest $request
     *
     * @return mixed
     */
    protected function buildSignedContinuationUrl(EmailVerificationRequest $request)
    {
        $params          = $request->getUrlParamsToSign();
        $params['token'] = $this->email_token_service->createToken($params['token']);
        return $request->getContinuationUrl($this->url_provider, $params);
    }

    /**
     * @param \Ingenerator\Warden\Core\Interactor\EmailVerificationRequest $request
     *
     * @return \Ingenerator\Warden\Core\RateLimit\LeakyBucketStatus
     */
    protected function checkRateLimit(EmailVerificationRequest $request)
    {
        return $this->leaky_bucket->attemptRequest(
            'warden.email.'.$request->getAction(),
            $request->getEmail()
        );
    }

}
