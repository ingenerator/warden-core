<?php
/**
 * @author    Andrew Coulton <andrew@ingenerator.com>
 * @licence   proprietary
 */

namespace Ingenerator\Warden\Core\Interactor;

use Ingenerator\Warden\Core\Config\Configuration;
use Ingenerator\Warden\Core\Entity\User;
use Ingenerator\Warden\Core\Repository\DuplicateUserException;
use Ingenerator\Warden\Core\Repository\UserRepository;
use Ingenerator\Warden\Core\Support\EmailConfirmationTokenService;
use Ingenerator\Warden\Core\Support\PasswordHasher;
use Ingenerator\Warden\Core\UserSession\UserSession;
use Ingenerator\Warden\Core\Validator\Validator;

class UserRegistrationInteractor extends AbstractTokenValidatingInteractor
{
    /**
     * @var Configuration
     */
    protected $configuration;

    /**
     * @var PasswordHasher
     */
    protected $password_hasher;

    /**
     * @var UserSession
     */
    protected $user_session;

    /**
     * @var UserRepository
     */
    protected $users_repo;

    /**
     * @var Validator
     */
    protected $validator;

    public function __construct(
        Configuration $configuration,
        Validator $validator,
        PasswordHasher $password_hasher,
        EmailConfirmationTokenService $email_token_service,
        UserRepository $users_repo,
        UserSession $user_session
    ) {
        parent::__construct($email_token_service);
        $this->validator       = $validator;
        $this->users_repo      = $users_repo;
        $this->password_hasher = $password_hasher;
        $this->configuration   = $configuration;
        $this->user_session    = $user_session;
    }

    /**
     * @param UserRegistrationRequest $request
     *
     * @return UserRegistrationResponse
     */
    public function execute(UserRegistrationRequest $request)
    {
        if ($errors = $this->validator->validate($request)) {
            return UserRegistrationResponse::validationFailed($errors);
        }

        $token_state = $this->validateToken($request);
        if ( ! $token_state->isValid()) {
            if ($request->hasToken()) {
                return UserRegistrationResponse::badEmailConfirmation();
            } else {
                return UserRegistrationResponse::emailConfirmationRequired();
            }
        }

        try {
            $user = $this->createUser($request);
            $this->users_repo->save($user);
        } catch (DuplicateUserException $e) {
            return UserRegistrationResponse::duplicateUserEmail($e->getEmail());
        }

        if ($user->isActive()) {
            $this->user_session->login($user);
        }

        return UserRegistrationResponse::success($user);
    }

    /**
     * @param UserRegistrationRequest $request
     *
     * @return User
     */
    protected function createUser(UserRegistrationRequest $request)
    {
        $user = $this->users_repo->newUser();
        // Extra fields intentionally before core fields to ensure the request object cannot break core logic
        $request->populateExtraFields($user);
        $user->setEmail($request->getEmail());
        $user->setPasswordHash($this->password_hasher->hash($request->getPassword()));
        $this->setInitialActiveState($user, $request);

        return $user;
    }

    /**
     * @param User                    $user
     * @param UserRegistrationRequest $request
     */
    protected function setInitialActiveState(User $user, UserRegistrationRequest $request)
    {
        $user->setActive((bool) $request->getToken());
    }

    /**
     * Validate the token with the request.
     *
     * Note, if confirmation is not required then an empty token will be validated as OK
     *
     * @param \Ingenerator\Warden\Core\Interactor\UserRegistrationRequest $request
     *
     * @return \Ingenerator\Warden\Core\Interactor\TokenValidationResult
     */
    public function validateToken(UserRegistrationRequest $request)
    {
        if ($request->hasToken()) {
            return TokenValidationResult::withNoUser(
                $this->isTokenValid(
                    EmailVerificationRequest::forRegistration($request->getEmail()),
                    $request
                )
            );
        } elseif ( ! $this->configuration->isEmailConfirmationRequiredToRegister()) {
            return TokenValidationResult::withNoUser(TRUE);
        } else {
            return TokenValidationResult::withNoUser(FALSE);
        }
    }

}
