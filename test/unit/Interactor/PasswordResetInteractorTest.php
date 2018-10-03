<?php
/**
 * @author    Andrew Coulton <andrew@ingenerator.com>
 * @licence   proprietary
 */

namespace test\unit\Ingenerator\Warden\Core\Interactor;


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
use test\mock\Ingenerator\Warden\Core\Repository\SaveSpyingUserRepository;
use test\mock\Ingenerator\Warden\Core\Support\InsecureJSONTokenServiceStub;
use test\mock\Ingenerator\Warden\Core\Support\ReversingPassswordHasherStub;
use test\mock\Ingenerator\Warden\Core\Support\ValidInvalidTokenServiceStub;
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
        $this->assertInstanceOf(
            'Ingenerator\Warden\Core\Interactor\PasswordResetInteractor',
            $this->newSubject()
        );
    }

    public function provider_validate_password_reset()
    {
        $user_15 = UserStub::fromArray(
            ['id' => 15, 'password_hash' => 'current', 'email' => 'foo@bar.com']
        );
        return [
            [
                [],
                ['user_id' => 999, 'token' => 'invalid'],
                ['user' => NULL, 'user_email' => NULL, 'is_valid' => FALSE]
            ],
            [
                [$user_15],
                ['user_id' => 999, 'token' => 'invalid'],
                ['user' => NULL, 'user_email' => NULL, 'is_valid' => FALSE]
            ],
            [
                // CAUTION: exposing the user email on an invalid token could allow scraping of user emails
                // Only expose the user outside the system if the token is valid
                [$user_15],
                ['user_id' => 15, 'token' => 'invalid'],
                ['user' => $user_15, 'user_email' => 'foo@bar.com', 'is_valid' => FALSE]
            ],
            [
                // Token expires if the user has already changed their password
                [$user_15],

                [
                    'user_id' => 15,
                    'token'   => [
                        'user_id'         => 15,
                        'action'          => EmailVerificationRequest::RESET_PASSWORD,
                        'current_pw_hash' => 'previous',
                    ]
                ]
                ,
                ['user' => $user_15, 'user_email' => 'foo@bar.com', 'is_valid' => FALSE]
            ],
            [
                [$user_15],
                [
                    'user_id' => 15,
                    'token'   => [
                        'user_id'         => 15,
                        'action'          => EmailVerificationRequest::RESET_PASSWORD,
                        'current_pw_hash' => 'current',
                    ]
                ],
                ['user' => $user_15, 'user_email' => 'foo@bar.com', 'is_valid' => TRUE]
            ],
        ];
    }

    /**
     * @dataProvider provider_validate_password_reset
     */
    public function test_it_can_validate_password_reset_token($users, $request, $expect)
    {
        $this->email_token_service = new InsecureJSONTokenServiceStub;
        foreach ($users as $user) {
            $this->user_repo->save($user);
        }
        if (is_array($request['token'])) {
            $request['token'] = $this->email_token_service->createToken($request['token']);
        }
        $state = $this->newSubject()->validateToken(PasswordResetRequest::fromArray($request));
        $this->assertSame(
            $expect,
            [
                'user'       => $state->getUser(),
                'user_email' => $state->getUserEmail(),
                'is_valid'   => $state->isValid()
            ]
        );
    }

    public function test_it_fails_if_details_are_not_valid()
    {
        $this->validator = ValidatorStub::neverValid();
        $this->assertFailsWithCode(
            PasswordResetResponse::ERROR_DETAILS_INVALID,
            $this->executeWith([])
        );
    }

    /**
     * @testWith ["valid"]
     *           ["invalid"]
     */
    public function test_it_fails_if_user_does_not_exist_regardless_of_token_validity($token)
    {
        $result = $this->executeWith(['user_id' => 999, 'token' => $token]);
        $this->assertFailsWithCode(PasswordResetResponse::ERROR_UNKNOWN_USER, $result);
    }

    public function test_it_fails_if_invalid_email_confirmation_token_presented()
    {
        $this->user_repo->save(
            UserStub::fromArray(
                ['id' => 15, 'password_hash' => 'hashhash', 'email' => 'any.where@some.net']
            )
        );
        $result = $this->executeWith(['user_id' => 15, 'token' => 'invalid',]);
        $this->assertFailsWithCode(PasswordResetResponse::ERROR_TOKEN_INVALID, $result);
        $this->assertSame('any.where@some.net', $result->getEmail());
    }

    public function test_it_does_not_change_user_password_on_failure()
    {
        $user            = UserStub::fromArray(
            ['id' => 15, 'password_hash' => 'unchanged_hash', 'email' => 'any.where@some.net']
        );
        $this->user_repo = new SaveSpyingUserRepository([$user]);
        $this->executeWith(['user_id' => 15, 'token' => 'invalid']);
        $this->assertSame('unchanged_hash', $user->getPasswordHash());
        $this->user_repo->assertNothingSaved();
    }

    /**
     * @testWith [{"id": 19, "email": "any.where@some.net", "password_hash": "current_hash", "is_active": false}]
     *           [{"id": 19, "email": "any.where@some.net", "password_hash": "current_hash", "is_active": true}]
     */
    public function test_it_stores_and_saves_new_user_password_hash_on_success_activating_if_required(
        $user_data
    ) {
        $this->password_hasher = new ReversingPassswordHasherStub;
        $user                  = UserStub::fromArray($user_data);
        $this->user_repo       = new SaveSpyingUserRepository([$user]);
        $this->executeWith(
            [
                'user_id'      => 19,
                'token'        => 'valid',
                'new_password' => 'new_password',
            ]
        );
        $this->assertSame('drowssap_wen', $user->getPasswordHash());
        $this->assertTrue($user->isActive(), 'User should be active');
        $this->user_repo->assertOneSaved($user);
    }

    public function test_it_does_not_login_user_on_failure()
    {
        $user = UserStub::fromArray(
            ['id' => 15, 'password_hash' => 'unchanged_hash', 'email' => 'any.where@some.net']
        );
        $this->user_repo->save($user);
        $this->executeWith(['user_id' => 15, 'token' => 'invalid']);
        $this->assertFalse($this->user_session->isAuthenticated());
    }

    public function test_it_logs_in_user_on_success()
    {
        $user = UserStub::fromArray(
            ['id' => 15, 'password_hash' => 'current_hash', 'email' => 'any.where@some.net']
        );
        $this->user_repo->save($user);
        $this->executeWith(['user_id' => 15, 'token' => 'valid']);
        $this->assertSame($user, $this->user_session->getUser());
    }

    public function setUp()
    {
        parent::setUp();
        $this->validator           = ValidatorStub::alwaysValid();
        $this->email_token_service = new ValidInvalidTokenServiceStub;
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

}
