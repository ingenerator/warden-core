<?php
/**
 * @author    Andrew Coulton <andrew@ingenerator.com>
 * @licence   proprietary
 */

namespace test\unit\Ingenerator\Warden\Core\Interactor;


use Ingenerator\Warden\Core\Interactor\ActivateAccountInteractor;
use Ingenerator\Warden\Core\Interactor\ActivateAccountRequest;
use Ingenerator\Warden\Core\Interactor\ActivateAccountResponse;
use Ingenerator\Warden\Core\Interactor\EmailVerificationRequest;
use Ingenerator\Warden\Core\Repository\ArrayUserRepository;
use Ingenerator\Warden\Core\Repository\UnknownUserException;
use Ingenerator\Warden\Core\Repository\UserRepository;
use Ingenerator\Warden\Core\Support\EmailConfirmationTokenService;
use Ingenerator\Warden\Core\UserSession\SimplePropertyUserSession;
use Ingenerator\Warden\Core\UserSession\UserSession;
use Ingenerator\Warden\Core\Validator\Validator;
use test\mock\Ingenerator\Warden\Core\Entity\UserStub;
use test\mock\Ingenerator\Warden\Core\Repository\SaveSpyingUserRepository;
use test\mock\Ingenerator\Warden\Core\Support\InsecureJSONTokenServiceStub;
use test\mock\Ingenerator\Warden\Core\Validator\ValidatorStub;

class ActivateAccountInteractorTest extends AbstractInteractorTest
{

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
        $this->assertInstanceOf(ActivateAccountInteractor::class, $this->newSubject());
    }

    public function test_it_fails_if_details_are_not_valid()
    {
        $this->validator = ValidatorStub::neverValid();
        $this->assertFailsWithCode(
            ActivateAccountResponse::ERROR_DETAILS_INVALID,
            $this->executeWith(['user_id' => 'joe@wrong'])
        );

    }

    public function test_it_throws_if_user_does_not_exist()
    {
        $this->expectException(UnknownUserException::class);
        $this->executeWith(['user_id' => 999]);
    }

    public function test_it_fails_if_invalid_email_confirmation_token_presented()
    {
        $this->user_repo->save(UserStub::fromArray(['id' => 12]));
        $result = $this->executeWith(
            [
                'user_id' => 12,
                'token'   => $this->givenValidToken(['user_id' => 999]),
            ]
        );
        $this->assertFailsWithCode(ActivateAccountResponse::ERROR_TOKEN_INVALID, $result);
    }

    public function test_it_does_not_make_active_on_failure()
    {
        $user            = UserStub::fromArray(
            ['id' => 15, 'email' => 'jo@b.com', 'is_active' => FALSE]
        );
        $this->user_repo = new SaveSpyingUserRepository([$user]);
        $this->executeWith(
            [
                'user_id' => 15,
                'token'   => 'invalid',
            ]
        );
        $this->assertFalse($user->isActive(), 'Should still be inactive');
        $this->user_repo->assertNothingSaved();
    }

    /**
     * @testWith [false]
     *           [true]
     */
    public function test_it_makes_active_and_saves_on_success_even_if_already_active($was_active)
    {
        // No point failing on an already-used link, just activate them again
        $user            = UserStub::fromArray(
            ['id' => 15, 'email' => 'old@td.com', 'is_active' => $was_active]
        );
        $this->user_repo = new SaveSpyingUserRepository([$user]);
        $result          = $this->executeWith(['user_id' => 15]);
        $this->assertSuccessful($result);
        $this->assertTrue($user->isActive(), 'Should be active');
        $this->user_repo->assertOneSaved($user);
    }

    public function test_it_does_not_login_user_on_failure()
    {
        $this->user_repo->save(UserStub::fromArray(['id' => 15, 'email' => 'old@td.com']));
        $this->executeWith(['user_id' => 15, 'token' => 'invalid']);
        $this->assertFalse($this->user_session->isAuthenticated());
    }

    public function test_it_does_not_login_user_if_already_a_user_authenticated()
    {
        $current_user = UserStub::fromArray(['id' => 15, 'email' => 'whoever@td.com']);
        $this->user_session->login($current_user);
        $this->user_repo->save(UserStub::fromArray(['id' => 15, 'email' => 'old@td.com']));
        $result = $this->executeWith(['user_id' => 15]);
        $this->assertSuccessful($result);
        $this->assertSame($current_user, $this->user_session->getUser());
    }

    public function test_it_logs_in_user_on_success()
    {
        $user = UserStub::fromArray(['id' => 15, 'email' => 'old@td.com']);
        $this->user_repo->save($user);
        $result = $this->executeWith(['user_id' => 15]);
        $this->assertSuccessful($result);
        $this->assertTrue($this->user_session->isAuthenticated(), 'Should be authenticated');
        $this->assertSame($user, $this->user_session->getUser());
    }

    public function setUp(): void
    {
        parent::setUp();
        $this->validator           = ValidatorStub::alwaysValid();
        $this->email_token_service = new InsecureJSONTokenServiceStub;
        $this->user_repo           = new ArrayUserRepository;
        $this->user_session        = new SimplePropertyUserSession;
    }

    protected function newSubject()
    {
        return new ActivateAccountInteractor(
            $this->validator,
            $this->email_token_service,
            $this->user_repo,
            $this->user_session
        );
    }

    /**
     * @param array $details
     *
     * @return \Ingenerator\Warden\Core\Interactor\ActivateAccountResponse
     */
    protected function executeWith(array $details)
    {
        if ( ! isset($details['token'])) {
            $details['token'] = $this->givenValidToken(['user_id' => $details['user_id']]);
        }
        return $this->newSubject()->execute(ActivateAccountRequest::fromArray($details));
    }

    /**
     * @param string $email
     *
     * @return string
     */
    protected function givenValidToken(array $params)
    {

        $params = \array_merge(
            [
                'action'        => EmailVerificationRequest::ACTIVATE_ACCOUNT,
                'user_id'       => 1,
            ],
            $params
        );
        return $this->email_token_service->createToken($params);
    }

}
