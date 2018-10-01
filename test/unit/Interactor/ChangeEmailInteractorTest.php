<?php
/**
 * @author    Andrew Coulton <andrew@ingenerator.com>
 * @licence   proprietary
 */

namespace test\unit\Ingenerator\Warden\Core\Interactor;


use Ingenerator\Warden\Core\Interactor\ChangeEmailInteractor;
use Ingenerator\Warden\Core\Interactor\ChangeEmailRequest;
use Ingenerator\Warden\Core\Interactor\ChangeEmailResponse;
use Ingenerator\Warden\Core\Interactor\EmailVerificationRequest;
use Ingenerator\Warden\Core\Repository\ArrayUserRepository;
use Ingenerator\Warden\Core\Repository\UserRepository;
use Ingenerator\Warden\Core\Support\EmailConfirmationTokenService;
use Ingenerator\Warden\Core\UserSession\SimplePropertyUserSession;
use Ingenerator\Warden\Core\UserSession\UserSession;
use Ingenerator\Warden\Core\Validator\Validator;
use test\mock\Ingenerator\Warden\Core\Entity\UserStub;
use test\mock\Ingenerator\Warden\Core\Repository\SaveSpyingUserRepository;
use test\mock\Ingenerator\Warden\Core\Support\InsecureJSONTokenServiceStub;
use test\mock\Ingenerator\Warden\Core\Validator\ValidatorStub;

class ChangeEmailInteractorTest extends AbstractInteractorTest
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
        $this->assertInstanceOf(ChangeEmailInteractor::class, $this->newSubject());
    }

    public function test_it_fails_if_details_are_not_valid()
    {
        $this->validator = ValidatorStub::neverValid();
        $this->assertFailsWithCode(
            ChangeEmailResponse::ERROR_DETAILS_INVALID,
            $this->executeWith([])
        );

    }

    /**
     * @expectedException \Ingenerator\Warden\Core\Repository\UnknownUserException
     */
    public function test_it_throws_if_user_does_not_exist()
    {
        $result = $this->executeWith(['user_id' => 999]);
        $this->assertFailsWithCode(ChangeEmailResponse::ERROR_UNKNOWN_USER, $result);
        $this->assertSame('unknown@foo.bar', $result->getNewEmail());
    }

    /**
     * @testWith ["foo@bar.com", "foo@bar.com"]
     *           ["foo@bar.com", "foO@BAR.com"]
     *           ["foo@bar.com", " foo@bar.com "]
     */
    public function test_it_fails_if_user_email_already_registered_to_someone_else(
        $existing_email,
        $new_email
    ) {
        $this->user_repo->save(UserStub::withEmail($existing_email));
        $this->user_repo->save(UserStub::fromArray(['id' => 123, 'email' => 'anything@td.com']));
        $result = $this->executeWith(
            [
                'user_id' => 123,
                'email'   => $new_email,
                'token'   => $this->givenValidToken(
                    [
                        'email'         => 'foo@bar.com',
                        'user_id'       => 123,
                        'current_email' => 'anything@td.com'
                    ]
                )
            ]
        );
        $this->assertFailsWithCode(ChangeEmailResponse::ERROR_ALREADY_REGISTERED, $result);
    }

    public function test_it_fails_if_invalid_email_confirmation_token_presented()
    {
        $this->user_repo->save(UserStub::fromArray(['id' => 12, 'email' => 'any.where@some.net']));
        $result = $this->executeWith(
            [
                'user_id' => 12,
                'email'   => 'new@some.net',
                'token'   => $this->givenValidToken(
                    ['user_id' => 999, 'email' => 'any.where@some.net']
                ),
            ]
        );
        $this->assertFailsWithCode(ChangeEmailResponse::ERROR_TOKEN_INVALID, $result);
        $this->assertSame('new@some.net', $result->getNewEmail());
    }

    public function test_it_fails_if_email_has_already_changed_since_token_was_generated()
    {
        $this->user_repo->save(UserStub::fromArray(['id' => 12, 'email' => 'new@some.net']));
        $result = $this->executeWith(
            [
                'user_id' => 12,
                'email'   => 'new@some.net',
                'token'   => $this->givenValidToken(
                    ['user_id' => 12, 'email' => 'new@some.net', 'current_email' => 'old@some.net']
                ),
            ]
        );
        $this->assertFailsWithCode(ChangeEmailResponse::ERROR_TOKEN_INVALID, $result);
        $this->assertSame('new@some.net', $result->getNewEmail());
    }

    public function test_it_does_not_change_email_on_failure()
    {
        $user            = UserStub::fromArray(['id' => 15, 'email' => 'jo@b.com']);
        $this->user_repo = new SaveSpyingUserRepository([$user]);
        $this->executeWith(
            [
                'user_id' => 15,
                'email'   => 'bad@m.an',
                'token'   => 'invalid',
            ]
        );
        $this->assertSame('jo@b.com', $user->getEmail());
        $this->user_repo->assertNothingSaved();
    }

    public function test_it_stores_and_saves_new_user_email_on_success()
    {
        $user            = UserStub::fromArray(['id' => 15, 'email' => 'old@td.com']);
        $this->user_repo = new SaveSpyingUserRepository([$user]);
        $result          = $this->executeWith(
            [
                'user_id' => 15,
                'email'   => 'new@td.com',
                'token'   => $this->givenValidToken(
                    ['user_id' => 15, 'email' => 'new@td.com', 'current_email' => 'old@td.com']
                ),
            ]
        );
        $this->assertSuccessful($result);
        $this->assertSame('new@td.com', $user->getEmail());
        $this->user_repo->assertOneSaved($user);
    }

    public function test_it_does_not_login_user_on_failure()
    {
        $this->user_repo->save(UserStub::fromArray(['id' => 15, 'email' => 'old@td.com']));
        $this->executeWith(
            ['user_id' => 15, 'email' => 'any.where@some.net', 'token' => 'invalid']
        );
        $this->assertFalse($this->user_session->isAuthenticated());
    }

    public function test_it_does_not_login_user_if_already_a_user_authenticated()
    {
        $current_user = UserStub::fromArray(['id' => 15, 'email' => 'whoever@td.com']);
        $this->user_session->login($current_user);
        $this->user_repo->save(UserStub::fromArray(['id' => 15, 'email' => 'old@td.com']));
        $result = $this->executeWith(
            [
                'user_id' => 15,
                'email'   => 'new@td.com',
                'token'   => $this->givenValidToken(
                    ['user_id' => 15, 'email' => 'new@td.com', 'current_email' => 'old@td.com']
                ),
            ]
        );
        $this->assertSuccessful($result);
        $this->assertSame($current_user, $this->user_session->getUser());
    }

    public function test_it_logs_in_user_on_success()
    {
        $user = UserStub::fromArray(['id' => 15, 'email' => 'old@td.com']);
        $this->user_repo->save($user);
        $result = $this->executeWith(
            [
                'user_id' => 15,
                'email'   => 'new@td.com',
                'token'   => $this->givenValidToken(
                    ['user_id' => 15, 'email' => 'new@td.com', 'current_email' => 'old@td.com']
                ),
            ]
        );
        $this->assertSuccessful($result);
        $this->assertTrue($this->user_session->isAuthenticated(), 'Should be authenticated');
        $this->assertSame($user, $this->user_session->getUser());
    }

    public function setUp()
    {
        parent::setUp();
        $this->validator           = ValidatorStub::alwaysValid();
        $this->email_token_service = new InsecureJSONTokenServiceStub;
        $this->user_repo           = new ArrayUserRepository;
        $this->user_session        = new SimplePropertyUserSession;
    }

    protected function newSubject()
    {
        return new ChangeEmailInteractor(
            $this->validator,
            $this->email_token_service,
            $this->user_repo,
            $this->user_session
        );
    }

    /**
     * @param array $details
     *
     * @return ChangeEmailResponse
     */
    protected function executeWith(array $details)
    {
        return $this->newSubject()->execute(ChangeEmailRequest::fromArray($details));
    }

    /**
     * @param string $email
     *
     * @return string
     */
    protected function givenValidToken(array $params)
    {

        $params = array_merge(
            [
                'email'         => 'new@bar.com',
                'action'        => EmailVerificationRequest::CHANGE_EMAIL,
                'user_id'       => 1,
                'current_email' => 'old@bar.com',
            ],
            $params
        );
        return $this->email_token_service->createToken($params);
    }

}
