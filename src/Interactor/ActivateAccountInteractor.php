<?php
/**
 * @author    Andrew Coulton <andrew@ingenerator.com>
 * @licence   proprietary
 */

namespace Ingenerator\Warden\Core\Interactor;


use Ingenerator\Warden\Core\Entity\User;
use Ingenerator\Warden\Core\Repository\UserRepository;
use Ingenerator\Warden\Core\Support\EmailConfirmationTokenService;
use Ingenerator\Warden\Core\UserSession\UserSession;
use Ingenerator\Warden\Core\Validator\Validator;

class ActivateAccountInteractor
{
    /**
     * @var \Ingenerator\Warden\Core\Validator\Validator
     */
    protected $validator;

    /**
     * @var \Ingenerator\Warden\Core\Support\EmailConfirmationTokenService
     */
    protected $email_token_service;

    /**
     * @var \Ingenerator\Warden\Core\Repository\UserRepository
     */
    protected $users_repo;

    /**
     * @var \Ingenerator\Warden\Core\UserSession\UserSession
     */
    protected $user_session;

    public function __construct(
        Validator $validator,
        EmailConfirmationTokenService $email_token_service,
        UserRepository $users_repo,
        UserSession $user_session
    ) {
        $this->validator           = $validator;
        $this->email_token_service = $email_token_service;
        $this->users_repo          = $users_repo;
        $this->user_session        = $user_session;
    }

    /**
     * @param \Ingenerator\Warden\Core\Interactor\ActivateAccountRequest $request
     *
     * @return \Ingenerator\Warden\Core\Interactor\ActivateAccountResponse
     */
    public function execute(ActivateAccountRequest $request)
    {
        if ($errors = $this->validator->validate($request)) {
            return ActivateAccountResponse::validationFailed($errors);
        }

        $user = $this->users_repo->load($request->getUserId());

        if ( ! $this->isTokenValid($request, $user)) {
            return ActivateAccountResponse::invalidToken();
        }

        $this->activateAndLoginUser($user);
        return ActivateAccountResponse::success();
    }

    protected function isTokenValid(ActivateAccountRequest $request, User $user)
    {
        $params = [
            'action'  => EmailVerificationRequest::ACTIVATE_ACCOUNT,
            'user_id' => $user->getId(),
        ];

        return $this->email_token_service->isValid($request->getToken(), $params);
    }

    /**
     * @param \Ingenerator\Warden\Core\Entity\User $user
     */
    protected function activateAndLoginUser(User $user)
    {
        $user->setActive(TRUE);
        $this->users_repo->save($user);

        if ( ! $this->user_session->isAuthenticated()) {
            $this->user_session->login($user);
        }
    }
}
