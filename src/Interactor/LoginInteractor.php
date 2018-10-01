<?php
/**
 * @author    Andrew Coulton <andrew@ingenerator.com>
 * @licence   proprietary
 */

namespace Ingenerator\Warden\Core\Interactor;


use Ingenerator\Warden\Core\Entity\User;
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

    public function __construct(
        Validator $validator,
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
    }


    /**
     * @param LoginRequest $request
     *
     * @return LoginResponse
     */
    public function execute(LoginRequest $request)
    {
        if ($this->session->isAuthenticated()) {
            throw new \LogicException('Cannot handle a login request when a user is already logged in');
        }

        if ($errors = $this->validator->validate($request)) {
            return LoginResponse::validationFailed($errors);
        }

        if ( ! $user = $this->user_repo->findByEmail($request->getEmail())) {
            return LoginResponse::notRegistered($request->getEmail());
        }

        if ( ! $user->isActive()) {
            return LoginResponse::notActive($user);
        }

        if ( ! $this->hasher->isCorrect($request->getPassword(), $user->getPasswordHash())) {
            return $this->handlePasswordIncorrect($user);
        }

        $this->upgradePasswordHashIfRequired($user, $request->getPassword());
        $this->session->login($user);

        return LoginResponse::success($user);
    }

    /**
     * @param User $user
     *
     * @return static
     */
    protected function handlePasswordIncorrect(User $user)
    {
        $request = EmailVerificationRequest::forPasswordReset($user);
        $result  = $this->email_verification->execute($request);
        if ($result->isFailureCode(EmailVerificationResponse::ERROR_RATE_LIMITED)) {
            return LoginResponse::passwordIncorrectRateLimited($user, $result->canRetryAfter());
        } elseif ( ! $result->wasSuccessful()) {
            throw new \UnexpectedValueException(
                'Password reset verification failed: '.$result->getFailureCode()
            );
        }

        return LoginResponse::passwordIncorrect($user);
    }

    protected function upgradePasswordHashIfRequired(User $user, $password)
    {
        if ($this->hasher->needsRehash($user->getPasswordHash())) {
            $user->setPasswordHash($this->hasher->hash($password));
            $this->user_repo->save($user);
        }
    }
}
