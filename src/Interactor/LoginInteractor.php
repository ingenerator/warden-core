<?php
/**
 * @author    Andrew Coulton <andrew@ingenerator.com>
 * @licence   proprietary
 */

namespace Ingenerator\Warden\Core\Interactor;


use Ingenerator\Warden\Core\Entity\User;
use Ingenerator\Warden\Core\RateLimit\LeakyBucket;
use Ingenerator\Warden\Core\Repository\UserRepository;
use Ingenerator\Warden\Core\Support\PasswordHasher;
use Ingenerator\Warden\Core\UserSession\UserSession;
use Ingenerator\Warden\Core\Validator\Validator;

class LoginInteractor
{
    /**
     * @var Validator
     */
    protected $validator;
    /**
     * @var PasswordHasher
     */
    protected $hasher;
    /**
     * @var UserSession
     */
    protected $session;
    /**
     * @var UserRepository
     */
    protected $user_repo;
    /**
     * @var EmailVerificationInteractor
     */
    protected $email_verification;
    /**
     * @var \Ingenerator\Warden\Core\RateLimit\LeakyBucket
     */
    protected $leaky_bucket;

    public function __construct(
        Validator $validator,
        LeakyBucket $leaky_bucket,
        UserRepository $user_repo,
        PasswordHasher $hasher,
        UserSession $session,
        EmailVerificationInteractor $email_verification
    ) {
        $this->validator          = $validator;
        $this->user_repo          = $user_repo;
        $this->hasher             = $hasher;
        $this->session            = $session;
        $this->email_verification = $email_verification;
        $this->leaky_bucket       = $leaky_bucket;
    }


    /**
     * @param LoginRequest $request
     *
     * @return LoginResponse
     */
    public function execute(LoginRequest $request)
    {
        if ($this->session->isAuthenticated()) {
            throw new \LogicException(
                'Cannot handle a login request when a user is already logged in'
            );
        }

        if ($errors = $this->validator->validate($request)) {
            return LoginResponse::validationFailed($errors);
        }

        if ($rate_response = $this->checkLoginRateLimits($request)) {
            return $rate_response;
        }

        if ( ! $user = $this->user_repo->findByEmail($request->getEmail())) {
            return LoginResponse::notRegistered($request->getEmail());
        }

        if ( ! $this->hasher->isCorrect($request->getPassword(), $user->getPasswordHash())) {
            return $this->handlePasswordIncorrect($user);
        }

        if ( ! $user->isActive()) {
            return $this->handleInactiveUserWithCorrectPassword($user);
        }

        $this->upgradePasswordHashIfRequired($user, $request->getPassword());
        $this->session->login($user);

        return LoginResponse::success($user);
    }

    /**
     * @param User $user
     *
     * @return \Ingenerator\Warden\Core\Interactor\LoginResponse
     */
    protected function handlePasswordIncorrect(User $user)
    {
        $request = $this->doPasswordResetEmail($user);
        $result  = $this->email_verification->execute($request);
        if ($result->isFailureCode(EmailVerificationResponse::ERROR_RATE_LIMITED)) {
            return LoginResponse::passwordIncorrectRateLimited($user, $result->canRetryAfter());

        } elseif ($result->isFailureCode(EmailVerificationResponse::ERROR_DETAILS_INVALID)) {
            return LoginResponse::emailFailed($user);

        } elseif ( ! $result->wasSuccessful()) {
            throw new \UnexpectedValueException(
                'Password reset verification failed: '.$result->getFailureCode()
            );
        }

        return $user->isActive() ? LoginResponse::passwordIncorrect($user) : LoginResponse::notActive($user);
    }

    protected function doPasswordResetEmail(User $user): EmailVerificationRequest
    {
        return EmailVerificationRequest::forPasswordReset($user);
    }

    protected function handleInactiveUserWithCorrectPassword(User $user)
    {
        $request = EmailVerificationRequest::forActivation($user);
        $result  = $this->email_verification->execute($request);
        if ($result->isFailureCode(EmailVerificationResponse::ERROR_RATE_LIMITED)) {
            return LoginResponse::notActiveRateLimited($user, $result->canRetryAfter());

        } elseif ($result->isFailureCode(EmailVerificationResponse::ERROR_DETAILS_INVALID)) {
            return LoginResponse::emailFailed($user);

        } elseif ( ! $result->wasSuccessful()) {
            throw new \UnexpectedValueException(
                'Activation send failed: '.$result->getFailureCode()
            );
        }

        return LoginResponse::notActive($user);
    }

    protected function upgradePasswordHashIfRequired(User $user, $password)
    {
        if ($this->hasher->needsRehash($user->getPasswordHash())) {
            $user->setPasswordHash($this->hasher->hash($password));
            $this->user_repo->save($user);
        }
    }

    /**
     * @param \Ingenerator\Warden\Core\Interactor\LoginRequest $request
     *
     * @return \Ingenerator\Warden\Core\Interactor\LoginResponse|null
     */
    protected function checkLoginRateLimits(LoginRequest $request)
    {
        $failed_limits = [];
        $retry_times   = [];
        foreach ($this->getRateLimitsToCheck($request) as $request_type => $requester_id) {
            $status = $this->leaky_bucket->attemptRequest($request_type, $requester_id);
            if ($status->isRateLimited()) {
                $failed_limits[] = $request_type;
                $retry_times[]   = $status->getNextAvailableTime();
            }
        }

        if ( ! empty ($failed_limits)) {
            return LoginResponse::rateLimited(
                $request->getEmail(),
                \max($retry_times),
                $failed_limits
            );
        } else {
            return NULL;
        }
    }

    protected function getRateLimitsToCheck(LoginRequest $request)
    {
        return [
            'warden.login.user'   => $request->getEmail(),
            'warden.login.global' => 'all'
        ];
    }
}
