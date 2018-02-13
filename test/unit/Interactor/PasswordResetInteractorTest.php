<?php
/**
 * @author    Andrew Coulton <andrew@ingenerator.com>
 * @licence   proprietary
 */

namespace test\unit\Ingenerator\Warden\Core\Interactor;


use Ingenerator\Warden\Core\Entity\User;
use Ingenerator\Warden\Core\Interactor\EmailVerificationRequest;
use Ingenerator\Warden\Core\Interactor\PasswordResetInteractor;
use Ingenerator\Warden\Core\Interactor\PasswordResetRequest;
use Ingenerator\Warden\Core\Interactor\PasswordResetResponse;
use Ingenerator\Warden\Core\Repository\ArrayUserRepository;
use Ingenerator\Warden\Core\Repository\UserRepository;
use Ingenerator\Warden\Core\Support\EmailConfirmationTokenService;
use Ingenerator\Warden\Core\UserSession\SimplePropertyUserSession;
use Ingenerator\Warden\Core\UserSession\UserSession;
use Ingenerator\Warden\Core\Validator\Validator;
use test\mock\Ingenerator\Warden\Core\Entity\UserStub;
use test\mock\Ingenerator\Warden\Core\Support\InsecureJSONTokenServiceStub;
use test\mock\Ingenerator\Warden\Core\Support\ReversingPassswordHasherStub;
use test\mock\Ingenerator\Warden\Core\Validator\ValidatorStub;

class PasswordResetInteractorTest extends AbstractInteractorTest
{
    /**
     * @var ReversingPassswordHasherStub
     */
    protected $password_hasher;

    /**
     * @var EmailConfirmationTokenService
     */
    protected $email_token_service;

    /**
     * @var UserRepository
     */
    protected $user_repo;

    /**
     * @var UserSession
     */
    protected $user_session;

    /**
     * @var Validator
     */
    protected $validator;


    public function test_it_is_initialisable()
    {
        $this->assertInstanceOf('Ingenerator\Warden\Core\Interactor\PasswordResetInteractor', $this->newSubject());
    }

    public function test_it_fails_if_details_are_not_valid()
    {
        $this->validator = ValidatorStub::neverValid();
        $this->assertFailsWithCode(PasswordResetResponse::ERROR_DETAILS_INVALID, $this->executeWith([]));
    }

    public function test_it_fails_if_user_does_not_exist()
    {
        $result = $this->executeWith(
            [
                'email' => 'unknown@foo.bar',
                'token' => 'anything at all',
            ]
        );
        $this->assertFailsWithCode(PasswordResetResponse::ERROR_UNKNOWN_USER, $result);
        $this->assertSame('unknown@foo.bar', $result->getEmail());
    }

    public function test_it_fails_if_invalid_email_confirmation_token_presented()
    {
        $this->user_repo->save(UserStub::activeWithPasswordHash('any.where@some.net', 'hashhash'));
        $result = $this->executeWith(
            [
                'email' => 'any.where@some.net',
                'token' => $this->givenValidToken('someone.else@foo.bar', 'hashhash'),
            ]
        );
        $this->assertFailsWithCode(PasswordResetResponse::ERROR_TOKEN_INVALID, $result);
        $this->assertSame('any.where@some.net', $result->getEmail());
    }

    public function test_it_fails_if_password_has_changed_since_token_was_generated()
    {
        $this->user_repo->save(UserStub::activeWithPasswordHash('any.where@some.net', 'newhash'));
        $result = $this->executeWith(
            [
                'email' => 'any.where@some.net',
                'token' => $this->givenValidToken('any.where@some.net', 'oldhash'),
            ]
        );
        $this->assertFailsWithCode(PasswordResetResponse::ERROR_TOKEN_INVALID, $result);
        $this->assertSame('any.where@some.net', $result->getEmail());
    }

    public function test_it_is_successful_if_request_matches_current_user_even_if_case_incorrect()
    {
        $this->user_repo->save(UserStub::activeWithPasswordHash('any.where@some.net', 'current_hash'));
        $result = $this->executeWith(
            [
                'email' => 'any.Where@some.net',
                'token' => $this->givenValidToken('any.where@some.net', 'current_hash'),
            ]
        );
        $this->assertSuccessful($result);
        $this->assertSame('any.where@some.net', $result->getEmail());
    }

    public function test_it_does_not_change_user_password_on_failure()
    {
        $user            = UserStub::activeWithPasswordHash('any.where@some.net', 'unchanged_hash');
        $this->user_repo = new SaveSpyingUserRepository([$user]);
        $this->executeWith(
            ['email' => 'any.where@some.net', 'token' => 'invalid']
        );
        $this->assertSame('unchanged_hash', $user->getPasswordHash());
        $this->user_repo->assertNothingSaved();
    }

    public function test_it_stores_and_saves_new_user_password_hash_on_success()
    {
        $this->password_hasher = new ReversingPassswordHasherStub;
        $user                  = UserStub::activeWithPasswordHash('any.where@some.net', 'current_hash');
        $this->user_repo       = new SaveSpyingUserRepository([$user]);
        $this->executeWith(
            [
                'email'        => 'any.where@some.net',
                'token'        => $this->givenValidToken('any.where@some.net', 'current_hash'),
                'new_password' => 'new_password',
            ]
        );
        $this->assertSame('drowssap_wen', $user->getPasswordHash());
        $this->user_repo->assertOneSaved($user);
    }

    public function test_it_does_not_login_user_on_failure()
    {
        $user = UserStub::activeWithPasswordHash('any.where@some.net', 'unchanged_hash');
        $this->user_repo->save($user);
        $this->executeWith(['email' => 'any.where@some.net', 'token' => 'invalid']);
        $this->assertFalse($this->user_session->isAuthenticated());
    }

    public function test_it_logs_in_user_on_success()
    {
        $user = UserStub::activeWithPasswordHash('any.where@some.net', 'current_hash');
        $this->user_repo->save($user);
        $this->executeWith(
            [
                'email' => 'any.where@some.net',
                'token' => $this->givenValidToken('any.where@some.net', 'current_hash'),
            ]
        );
        $this->assertSame($user, $this->user_session->getUser());
    }

    public function setUp()
    {
        parent::setUp();
        $this->validator           = ValidatorStub::alwaysValid();
        $this->email_token_service = new InsecureJSONTokenServiceStub;
        $this->user_repo           = new ArrayUserRepository;
        $this->user_session        = new SimplePropertyUserSession;
        $this->password_hasher     = new ReversingPassswordHasherStub;
    }

    protected function newSubject()
    {
        return new PasswordResetInteractor(
            $this->validator,
            $this->password_hasher,
            $this->email_token_service,
            $this->user_repo,
            $this->user_session
        );
    }

    /**
     * @param array $details
     *
     * @return PasswordResetResponse
     */
    protected function executeWith(array $details)
    {
        return $this->newSubject()->execute(PasswordResetRequest::fromArray($details));
    }

    /**
     * @param string $email
     *
     * @return string
     */
    protected function givenValidToken($email, $old_hash)
    {
        return $this->email_token_service->createToken(
            [
                'action'          => EmailVerificationRequest::RESET_PASSWORD,
                'email'           => $email,
                'current_pw_hash' => $old_hash,
            ]
        );
    }

}


class SaveSpyingUserRepository extends ArrayUserRepository
{

    /**
     * @var User[]
     */
    protected $users_saved;

    public function __construct(array $users = [])
    {
        parent::__construct();
        foreach ($users as $user) {
            $this->save($user);
        }
        $this->users_saved = [];
    }

    public function save(User $user)
    {
        parent::save($user);
        $this->users_saved[] = clone($user);
    }

    public function assertNothingSaved()
    {
        \PHPUnit_Framework_Assert::assertEmpty($this->users_saved);
    }

    public function assertOneSaved(User $user)
    {
        \PHPUnit_Framework_Assert::assertEquals([$user], $this->users_saved);
    }

}
