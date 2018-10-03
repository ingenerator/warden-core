<?php
/**
 * @author    Andrew Coulton <andrew@ingenerator.com>
 * @licence   proprietary
 */

namespace Ingenerator\Warden\Core\Interactor;


use Ingenerator\Warden\Core\Entity\User;
use Ingenerator\Warden\Core\Repository\UnknownUserException;
use Ingenerator\Warden\Core\Repository\UserRepository;
use Ingenerator\Warden\Core\Support\EmailConfirmationTokenService;
use Ingenerator\Warden\Core\Support\PasswordHasher;
use Ingenerator\Warden\Core\UserSession\UserSession;
use Ingenerator\Warden\Core\Validator\Validator;

class PasswordResetInteractor extends AbstractTokenValidatingInteractor
{

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
        Validator $validator,
        PasswordHasher $password_hasher,
        EmailConfirmationTokenService $email_token_service,
        UserRepository $users_repo,
        UserSession $user_session
    ) {
        parent::__construct($email_token_service);
        $this->validator           = $validator;
        $this->users_repo          = $users_repo;
        $this->password_hasher     = $password_hasher;
        $this->user_session        = $user_session;
    }

    /**
     * @param PasswordResetRequest $request
     *
     * @return PasswordResetResponse
     */
    public function execute(PasswordResetRequest $request)
    {
        if ($errors = $this->validator->validate($request)) {
            return PasswordResetResponse::validationFailed($errors);
        }

        try {
            $user = $this->users_repo->load($request->getUserId());
        } catch (UnknownUserException $e) {
            return PasswordResetResponse::unknownUser();
        }

        if ( ! $this->isTokenValid(EmailVerificationRequest::forPasswordReset($user), $request)) {
            return PasswordResetResponse::invalidToken($user->getEmail());
        }

        if ( ! $user->isActive()) {
            $user->setActive(TRUE);
        }
        $user->setPasswordHash($this->password_hasher->hash($request->getNewPassword()));
        $this->users_repo->save($user);
        $this->user_session->login($user);

        return PasswordResetResponse::success($user->getEmail());
    }

}
