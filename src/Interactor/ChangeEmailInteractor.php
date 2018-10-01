<?php
/**
 * @author    Andrew Coulton <andrew@ingenerator.com>
 * @licence   proprietary
 */

namespace Ingenerator\Warden\Core\Interactor;


use Ingenerator\Warden\Core\Entity\User;
use Ingenerator\Warden\Core\Repository\DuplicateUserException;
use Ingenerator\Warden\Core\Repository\UserRepository;
use Ingenerator\Warden\Core\Support\EmailConfirmationTokenService;
use Ingenerator\Warden\Core\UserSession\UserSession;
use Ingenerator\Warden\Core\Validator\Validator;

class ChangeEmailInteractor
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
     * @param \Ingenerator\Warden\Core\Interactor\ChangeEmailRequest $request
     *
     * @return \Ingenerator\Warden\Core\Interactor\ChangeEmailResponse
     */
    public function execute(ChangeEmailRequest $request)
    {
        if ($errors = $this->validator->validate($request)) {
            return ChangeEmailResponse::validationFailed($errors);
        }

        $user = $this->users_repo->load($request->getUserId());

        if ( ! $this->isTokenValid($request, $user)) {
            return ChangeEmailResponse::invalidToken($request->getEmail());
        }

        try {
            $this->updateAndLoginUser($request, $user);
            return ChangeEmailResponse::success($request->getEmail());
        } catch (DuplicateUserException $e) {
            return ChangeEmailResponse::duplicateUserEmail($request->getEmail());
        }
    }

    protected function isTokenValid(ChangeEmailRequest $request, User $user)
    {
        $params = [
            'email'         => $request->getEmail(),
            'action'        => EmailVerificationRequest::CHANGE_EMAIL,
            'user_id'       => $user->getId(),
            'current_email' => $user->getEmail(),
        ];

        return $this->email_token_service->isValid($request->getToken(), $params);
    }

    /**
     * @param \Ingenerator\Warden\Core\Interactor\ChangeEmailRequest $request
     * @param \Ingenerator\Warden\Core\Entity\User                   $user
     */
    protected function updateAndLoginUser(ChangeEmailRequest $request, User $user)
    {
        $user->setEmail($request->getEmail());
        $this->users_repo->save($user);
        
        if ( ! $this->user_session->isAuthenticated()) {
            $this->user_session->login($user);
        }
    }
}
