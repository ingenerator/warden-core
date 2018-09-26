<?php
/**
 * @author    Andrew Coulton <andrew@ingenerator.com>
 * @licence   proprietary
 */

namespace Ingenerator\Warden\Core\Interactor;


use Ingenerator\Warden\Core\Entity\User;
use Ingenerator\Warden\Core\Repository\UserRepository;
use Ingenerator\Warden\Core\Support\EmailConfirmationTokenService;
use Ingenerator\Warden\Core\Support\PasswordHasher;
use Ingenerator\Warden\Core\UserSession\UserSession;
use Ingenerator\Warden\Core\Validator\Validator;

class PasswordResetInteractor
{

    /**
     * @var EmailConfirmationTokenService
     */
    protected $email_token_service;

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
        $this->validator           = $validator;
        $this->users_repo          = $users_repo;
        $this->password_hasher     = $password_hasher;
        $this->email_token_service = $email_token_service;
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

        if ( ! $user = $this->users_repo->findByEmail($request->getEmail())) {
            return PasswordResetResponse::unknownUser($request->getEmail());
        }

        if ( ! $this->isTokenValid($request->getToken(), $user)) {
            return PasswordResetResponse::invalidToken($request->getEmail());
        }

        $user->setPasswordHash($this->password_hasher->hash($request->getNewPassword()));
        $this->users_repo->save($user);
        $this->user_session->login($user);

        return PasswordResetResponse::success($user->getEmail());
    }

    protected function isTokenValid($token, User $user)
    {
        $params = [
            'action'          => EmailVerificationRequest::RESET_PASSWORD,
            'email'           => $user->getEmail(),
            'current_pw_hash' => $user->getPasswordHash(),
        ];

        return $this->email_token_service->isValid($token, $params);
    }
}
