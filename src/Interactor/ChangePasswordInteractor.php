<?php
/**
 * @author    Andrew Coulton <andrew@ingenerator.com>
 * @licence   proprietary
 */

namespace Ingenerator\Warden\Core\Interactor;


use Ingenerator\Warden\Core\Repository\UserRepository;
use Ingenerator\Warden\Core\Support\PasswordHasher;
use Ingenerator\Warden\Core\Validator\Validator;

class ChangePasswordInteractor
{
    /**
     * @var \Ingenerator\Warden\Core\Validator\Validator
     */
    protected $validator;

    /**
     * @var \Ingenerator\Warden\Core\Support\PasswordHasher
     */
    protected $hasher;

    /**
     * @var \Ingenerator\Warden\Core\Repository\UserRepository
     */
    protected $user_repository;

    public function __construct(
        Validator $validator,
        PasswordHasher $hasher,
        UserRepository $user_repository
    ) {
        $this->validator       = $validator;
        $this->hasher          = $hasher;
        $this->user_repository = $user_repository;
    }


    /**
     * @param \Ingenerator\Warden\Core\Interactor\ChangePasswordRequest $request
     *
     * @return \Ingenerator\Warden\Core\Interactor\ChangePasswordResponse
     */
    public function execute(ChangePasswordRequest $request)
    {
        if ($errors = $this->validateRequest($request)) {
            return ChangePasswordResponse::validationFailed($errors);
        }

        $user = $request->getUser();
        $user->setPasswordHash($this->hasher->hash($request->getNewPassword()));
        $this->user_repository->save($user);

        return ChangePasswordResponse::success($user);
    }

    /**
     * @param \Ingenerator\Warden\Core\Interactor\ChangePasswordRequest $request
     *
     * @return string[]
     */
    protected function validateRequest(ChangePasswordRequest $request)
    {
        if ($errors = $this->validator->validate($request)) {
            return $errors;
        };

        if ( ! $this->hasher->isCorrect(
            $request->getCurrentPassword(),
            $request->getUser()->getPasswordHash()
        )) {
            return ['current_password' => 'The current password you entered was not correct.'];
        }

        return NULL;
    }
}
