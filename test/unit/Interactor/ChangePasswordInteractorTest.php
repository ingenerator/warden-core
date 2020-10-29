<?php
/**
 * @author    Andrew Coulton <andrew@ingenerator.com>
 * @licence   proprietary
 */

namespace test\unit\Ingenerator\Warden\Core\Interactor;


use Ingenerator\Warden\Core\Interactor\ChangeEmailResponse;
use Ingenerator\Warden\Core\Interactor\ChangePasswordInteractor;
use Ingenerator\Warden\Core\Interactor\ChangePasswordRequest;
use Ingenerator\Warden\Core\Interactor\ChangePasswordResponse;
use Ingenerator\Warden\Core\Repository\ArrayUserRepository;
use Ingenerator\Warden\Core\Repository\UserRepository;
use Ingenerator\Warden\Core\Validator\Validator;
use test\mock\Ingenerator\Warden\Core\Entity\UserStub;
use test\mock\Ingenerator\Warden\Core\Repository\SaveSpyingUserRepository;
use test\mock\Ingenerator\Warden\Core\Support\ReversingPassswordHasherStub;
use test\mock\Ingenerator\Warden\Core\Validator\ValidatorStub;

class ChangePasswordInteractorTest extends AbstractInteractorTest
{

    /**
     * @var \Ingenerator\Warden\Core\Support\PasswordHasher
     */
    protected $password_hasher;

    /**
     * @var UserRepository
     */
    protected $user_repo;

    /**
     * @var Validator
     */
    protected $validator;

    public function test_it_is_initialisable()
    {
        $this->assertInstanceOf(ChangePasswordInteractor::class, $this->newSubject());
    }

    public function test_it_fails_if_details_are_not_valid()
    {
        $this->validator = ValidatorStub::neverValid();
        $this->assertFailsWithCode(
            ChangePasswordResponse::ERROR_DETAILS_INVALID,
            $this->executeWith([])
        );
    }

    public function test_it_fails_if_current_password_not_correct()
    {
        $user            = UserStub::fromArray(['password_hash' => '12345678']);
        $this->user_repo = new SaveSpyingUserRepository([$user]);
        $result          = $this->executeWith(
            [
                'user'             => $user,
                'current_password' => '9999999',
                'new_password'     => 'abcdefg',
            ]
        );
        $this->assertFailsWithCode(ChangePasswordResponse::ERROR_DETAILS_INVALID, $result);
        $this->assertSame(
            ['current_password' => 'The current password you entered was not correct.'],
            $result->getValidationErrors()
        );
    }

    public function test_it_does_not_change_password_on_failure()
    {
        $this->validator = ValidatorStub::neverValid();
        $user            = UserStub::fromArray(['password_hash' => '12345678']);
        $this->user_repo = new SaveSpyingUserRepository([$user]);
        $this->executeWith(
            [
                'user'             => $user,
                'current_password' => '87654321',
                'new_password'     => 'abcdefg',
            ]
        );
        $this->assertSame('12345678', $user->getPasswordHash());
        $this->user_repo->assertNothingSaved();
    }

    public function test_it_stores_and_saves_user_password_hash_on_success()
    {
        $user            = UserStub::fromArray(['password_hash' => '12345678']);
        $this->user_repo = new SaveSpyingUserRepository([$user]);
        $result          = $this->executeWith(
            [
                'user'             => $user,
                'current_password' => '87654321',
                'new_password'     => 'abcdefg',
            ]
        );
        $this->assertSuccessful($result);
        $this->assertSame('gfedcba', $user->getPasswordHash());
        $this->user_repo->assertOneSaved($user);
    }

    public function setUp(): void
    {
        parent::setUp();
        $this->validator       = ValidatorStub::alwaysValid();
        $this->password_hasher = new ReversingPassswordHasherStub;
        $this->user_repo       = new ArrayUserRepository;
    }

    protected function newSubject()
    {
        return new ChangePasswordInteractor(
            $this->validator,
            $this->password_hasher,
            $this->user_repo
        );
    }

    /**
     * @param array $details
     *
     * @return ChangeEmailResponse
     */
    protected function executeWith(array $details)
    {
        return $this->newSubject()->execute(ChangePasswordRequest::fromArray($details));
    }

}
