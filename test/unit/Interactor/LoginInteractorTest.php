<?php
/**
 * @author    Andrew Coulton <andrew@ingenerator.com>
 * @licence   proprietary
 */

namespace test\unit\Ingenerator\Warden\Core\Interactor;


use Ingenerator\Warden\Core\Entity\SimpleUser;
use Ingenerator\Warden\Core\Entity\User;
use Ingenerator\Warden\Core\Interactor\AbstractResponse;
use Ingenerator\Warden\Core\Interactor\EmailVerificationInteractor;
use Ingenerator\Warden\Core\Interactor\EmailVerificationRequest;
use Ingenerator\Warden\Core\Interactor\EmailVerificationResponse;
use Ingenerator\Warden\Core\Interactor\LoginInteractor;
use Ingenerator\Warden\Core\Interactor\LoginRequest;
use Ingenerator\Warden\Core\Interactor\LoginResponse;
use Ingenerator\Warden\Core\RateLimit\LeakyBucket;
use Ingenerator\Warden\Core\RateLimit\LeakyBucketStatus;
use Ingenerator\Warden\Core\Repository\ArrayUserRepository;
use Ingenerator\Warden\Core\Repository\UserRepository;
use Ingenerator\Warden\Core\Support\PasswordHasher;
use Ingenerator\Warden\Core\UserSession\SimplePropertyUserSession;
use Ingenerator\Warden\Core\UserSession\UserSession;
use Ingenerator\Warden\Core\Validator\Validator;
use LogicException;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\MockObject\MockObject;
use test\mock\Ingenerator\Warden\Core\Entity\UserStub;
use test\mock\Ingenerator\Warden\Core\RateLimit\LeakyBucketStub;
use test\mock\Ingenerator\Warden\Core\Repository\SaveSpyingUserRepository;
use test\mock\Ingenerator\Warden\Core\Support\ReversingPassswordHasherStub;
use test\mock\Ingenerator\Warden\Core\Validator\ValidatorStub;

class LoginInteractorTest extends AbstractInteractorTest
{
    /**
     * @var EmailVerificationInteractorSpy
     */
    protected $email_verification;

    /**
     * @var LeakyBucket
     */
    protected $leaky_bucket;

    /**
     * @var PasswordHasher
     */
    protected $password_hasher;

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
        $this->assertInstanceOf(LoginInteractor::class, $this->newSubject());
    }

    public function test_it_throws_if_user_already_logged_in()
    {
        $this->user_session->login(new SimpleUser);
        $this->expectException(LogicException::class);
        $this->newSubject()->execute(LoginRequest::fromArray([]));
    }

    public function test_it_fails_if_request_is_invalid()
    {
        $this->validator = ValidatorStub::neverValid();
        $this->assertFailsWithCode(LoginResponse::ERROR_DETAILS_INVALID, $this->executeWith([]));
    }

    public function test_it_fails_if_user_is_not_registered()
    {
        $response = $this->executeWith(['email' => 'foo@bar.com', 'password' => '12345678']);
        $this->assertFailsWithCode(LoginResponse::ERROR_NOT_REGISTERED, $response);
        $this->assertSame('foo@bar.com', $response->getEmail());
    }

    public function provider_failed_login_users()
    {
        return [
            [
                UserStub::activeWithPasswordHash('foo@bar.com', 'abcdefgh'),
                LoginResponse::ERROR_PASSWORD_INCORRECT,
            ],
            [
                UserStub::inactiveWithPasswordHash('foo@bar.com', 'abcdefgh'),
                LoginResponse::ERROR_NOT_ACTIVE,
            ],
            [
                UserStub::inactiveWithPasswordHash('foo@bar.com', '87654321'),
                LoginResponse::ERROR_NOT_ACTIVE,
            ],
        ];
    }

    /**
     * @dataProvider provider_failed_login_users
     */
    public function test_it_fails_if_user_password_is_invalid_or_user_inactive(
        User $user,
        $expect_code
    ) {
        $this->password_hasher = new ReversingPassswordHasherStub;
        $this->user_repo->save($user);
        $response = $this->executeWith(['email' => 'foo@bar.com', 'password' => '12345678']);
        $this->assertFailsWithCode($expect_code, $response);
        $this->assertSame($user, $response->getUser());
    }

    /**
     * @testWith ["foo@bar.com", "foo@bar.com"]
     *           ["foo@bar.com", "foO@BAR.com"]
     *           ["foo@bar.com", " foo@bar.com "]
     */
    public function test_it_succeeds_with_correct_password_and_email_in_any_case(
        $actual_email,
        $entered_email
    ) {
        $this->password_hasher = new ReversingPassswordHasherStub();
        $user                  = UserStub::activeWithPasswordHash($actual_email, '12345678');
        $this->user_repo->save($user);
        $response = $this->executeWith(['email' => $entered_email, 'password' => '87654321']);
        $this->assertSuccessful($response);
        $this->assertSame($user, $response->getUser());
    }

    public function test_it_rate_limits_by_user_email()
    {
        $this->leaky_bucket = LeakyBucketStub::alwaysResponds(LeakyBucketStatus::allowed());
        $this->executeWith(['email' => 'foo@bar.com', 'password' => '87654321']);
        $this->leaky_bucket->assertOnlyRequestedOneOfType('warden.login.user', 'foo@bar.com');
    }

    public function test_it_rate_limits_by_global_rate()
    {
        $this->leaky_bucket = LeakyBucketStub::alwaysResponds(LeakyBucketStatus::allowed());
        $this->executeWith(['email' => 'foo@bar.com', 'password' => '87654321']);
        $this->leaky_bucket->assertOnlyRequestedOneOfType('warden.login.global', 'all');
    }

    public function provider_rate_limit_fails()
    {
        $plus_1 = new \DateTimeImmutable('+1 minutes');
        $plus_2 = new \DateTimeImmutable('+2 minutes');
        return [
            [
                // Both OK
                [
                    'warden.login.global' => ['all' => FALSE],
                    'warden.login.user'   => ['foo@bar.com' => FALSE]
                ],
                FALSE,
                NULL,
                NULL,
            ],
            [
                // Too many by user
                [
                    'warden.login.global' => ['all' => FALSE],
                    'warden.login.user'   => ['foo@bar.com' => $plus_1]
                ],
                TRUE,
                $plus_1,
                'warden.login.user'
            ],
            [
                // Too many globally
                [
                    'warden.login.global' => ['all' => $plus_2],
                    'warden.login.user'   => ['foo@bar.com' => FALSE]
                ],
                TRUE,
                $plus_2,
                'warden.login.global'
            ],
            [
                // Too many by user and globally, retry is the max
                [
                    'warden.login.global' => ['all' => $plus_2],
                    'warden.login.user'   => ['foo@bar.com' => $plus_1]
                ],
                TRUE,
                $plus_2,
                'warden.login.user,warden.login.global'
            ]
        ];
    }

    /**
     * @dataProvider provider_rate_limit_fails
     */
    public function test_it_fails_if_any_rate_limit_fails(
        $limits,
        $expect_fail,
        $expect_retry,
        $expect_detail
    ) {
        $this->password_hasher = new ReversingPassswordHasherStub();
        $user                  = UserStub::activeWithPasswordHash('foo@bar.com', '12345678');
        $this->user_repo->save($user);
        $this->leaky_bucket = LeakyBucketStub::withBuckets($limits);
        $result             = $this->executeWith(
            ['email' => 'foo@bar.com', 'password' => '87654321']
        );
        if ($expect_fail) {
            $this->assertFailsWithCode(LoginResponse::ERROR_RATE_LIMITED, $result);
        } else {
            $this->assertTrue($result->wasSuccessful(), 'Should succeed');
        }
        $this->assertEquals($expect_retry, $result->canRetryAfter());
        $this->assertSame($expect_detail, $result->getFailureDetail());
    }

    public function test_it_logs_in_user_in_session_on_successful_login()
    {
        $this->password_hasher = new ReversingPassswordHasherStub();
        $user                  = UserStub::activeWithPasswordHash('foo@bar.com', '12345678');
        $this->user_repo->save($user);
        $this->executeWith(['email' => 'foo@bar.com', 'password' => '87654321']);
        $this->assertTrue(
            $this->user_session->isAuthenticated(),
            'User session should be authenticated'
        );
        $this->assertSame(
            $user,
            $this->user_session->getUser(),
            'User session should be authenticated'
        );
    }

    public function test_it_upgrades_password_hash_on_successful_login_if_required()
    {
        $this->password_hasher = ReversingPassswordHasherStub::withRehashNeeded();
        $user                  = UserStub::activeWithPasswordHash('foo@bar.com', '12345678');
        $this->user_repo       = new SaveSpyingUserRepository([$user]);
        $this->executeWith(['email' => 'foo@bar.com', 'password' => '87654321']);
        $this->user_repo->assertOneSaved($user);
    }

    public function test_it_does_not_change_password_hash_on_successful_login_if_current_hash_is_secure(
    )
    {
        $this->password_hasher = ReversingPassswordHasherStub::withNoRehashNeeded();
        $user                  = UserStub::activeWithPasswordHash('foo@bar.com', '12345678');
        $this->user_repo       = new SaveSpyingUserRepository([$user]);
        $this->executeWith(['email' => 'foo@bar.com', 'password' => '87654321']);
        $this->user_repo->assertNothingSaved();
    }

    /**
     * @dataProvider provider_failed_login_users
     */
    public function test_it_does_not_change_password_hash_on_failed_login(User $user, $expect_code)
    {
        $this->password_hasher = ReversingPassswordHasherStub::withRehashNeeded();
        $this->user_repo       = new SaveSpyingUserRepository([$user]);
        $this->executeWith(['email' => 'foo@bar.com', 'password' => '12345678']);
        $this->user_repo->assertNothingSaved();
    }

    public function test_it_does_not_send_any_user_notification_on_successful_login()
    {
        $this->email_verification = $this->getMockExpectingNoCalls(
            EmailVerificationInteractor::class
        );
        $this->test_it_succeeds_with_correct_password_and_email_in_any_case(
            'foo@bar.com',
            'foo@bar.com'
        );
    }

    public function test_it_sends_user_notification_with_activation_url_on_inactive_user_with_correct_password(
    )
    {
        $this->email_verification = new EmailVerificationInteractorSpy;
        $user                     = UserStub::inactiveWithPasswordHash('foo@bar.com', '12345678');
        $this->user_repo->save($user);
        $this->executeWith(['email' => 'foo@bar.com', 'password' => '87654321']);
        $this->email_verification->assertExecutedOnceWith(
            EmailVerificationRequest::forActivation($user)
        );
    }

    /**
     * @testWith [true]
     *           [false]
     */
    public function test_it_triggers_email_verification_for_password_reset_on_incorrect_password_whether_or_not_active(
        $is_active
    ) {
        $this->email_verification = new EmailVerificationInteractorSpy;
        $user                     = UserStub::fromArray(
            ['email' => 'foo@bar.com', 'password_hash' => '12345678', 'is_active' => $is_active]
        );
        $this->user_repo->save($user);
        $this->executeWith(['email' => 'foo@bar.com', 'password' => 'wrong']);
        $this->email_verification->assertExecutedOnceWith(
            EmailVerificationRequest::forPasswordReset($user)
        );
    }

    public function provider_throttled_verifications()
    {
        return [
            [
                ['email' => 'foo@bar.com', 'password_hash' => '12345678', 'is_active' => FALSE],
                ['email' => 'foo@bar.com', 'password' => '87654321'],
                LoginResponse::ERROR_NOT_ACTIVE_ACTIVATION_THROTTLED,
            ],
            [
                ['email' => 'foo@bar.com', 'password_hash' => '12345678', 'is_active' => TRUE],
                ['email' => 'foo@bar.com', 'password' => 'wrong'],
                LoginResponse::ERROR_PASSWORD_INCORRECT_RESET_THROTTLED,
            ],
        ];
    }

    /**
     * @dataProvider provider_throttled_verifications
     */
    public function test_it_identifies_if_email_verification_throttled_for_activation_or_reset(
        $user_data,
        $request,
        $expect_code
    ) {
        $retry_after              = new \DateTimeImmutable();
        $this->email_verification = EmailVerificationInteractorSpy::willRespond(
            EmailVerificationResponse::rateLimited('foo@bar.com', $retry_after)
        );
        $user                     = UserStub::fromArray($user_data);
        $this->user_repo->save($user);
        $response = $this->executeWith($request);
        $this->assertFailsWithCode($expect_code, $response);
        $this->assertSame($retry_after, $response->canRetryAfter());
        $this->assertSame($user, $response->getUser());
    }


    public function provider_failed_email_verifications()
    {
        return [
            [
                ['email' => 'foo@bar.com', 'password_hash' => '12345678', 'is_active' => FALSE],
                ['email' => 'foo@bar.com', 'password' => '87654321'],
                LoginResponse::ERROR_NOT_ACTIVE_ACTIVATION_THROTTLED,
            ],
            [
                ['email' => 'foo@bar.com', 'password_hash' => '12345678', 'is_active' => TRUE],
                ['email' => 'foo@bar.com', 'password' => 'wrong'],
                LoginResponse::ERROR_PASSWORD_INCORRECT_RESET_THROTTLED,
            ],
        ];
    }

    /**
     * @dataProvider provider_failed_email_verifications
     */
    public function test_it_responds_if_email_verification_details_invalid_for_activation_or_reset(
        $user_data,
        $request
    ) {
        $this->email_verification = EmailVerificationInteractorSpy::willRespond(
            EmailVerificationResponse::validationFailed(['email' => 'you made that up'])
        );

        $user = UserStub::fromArray($user_data);
        $this->user_repo->save($user);
        $response = $this->executeWith($request);
        $this->assertFailsWithCode(LoginResponse::ERROR_EMAIL_VERIFICATION_FAILED, $response);
        $this->assertSame($user, $response->getUser());
    }


    public function setUp(): void
    {
        parent::setUp();
        $this->email_verification = new EmailVerificationInteractorSpy;
        $this->leaky_bucket       = LeakyBucketStub::alwaysResponds(LeakyBucketStatus::allowed());
        $this->validator          = ValidatorStub::alwaysValid();
        $this->password_hasher    = new ReversingPassswordHasherStub;
        $this->user_repo          = new ArrayUserRepository;
        $this->user_session       = new SimplePropertyUserSession;
    }

    protected function assertFailsWithCode($code, AbstractResponse $result)
    {
        $this->assertFalse(
            $this->user_session->isAuthenticated(),
            'User session should not be authenticated on failed request'
        );
        parent::assertFailsWithCode($code, $result);
    }

    /**
     * @param LoginRequest|array $request
     *
     * @return LoginResponse
     */
    protected function executeWith($request)
    {
        if ( ! $request instanceof LoginRequest) {
            $request = LoginRequest::fromArray($request);
        }

        return $this->newSubject()->execute($request);
    }

    protected function newSubject()
    {
        return new LoginInteractor(
            $this->validator,
            $this->leaky_bucket,
            $this->user_repo,
            $this->password_hasher,
            $this->user_session,
            $this->email_verification
        );
    }

    /**
     * @param string $className
     *
     * @return MockObject
     */
    protected function getMockExpectingNoCalls($className)
    {
        $mock = $this->getMockBuilder($className)->disableOriginalConstructor()->getMock();
        $mock->expects($this->never())->method($this->anything());

        return $mock;
    }
}


class EmailVerificationInteractorSpy extends EmailVerificationInteractor
{
    /**
     * @var EmailVerificationRequest[]
     */
    protected $calls = [];

    protected $response;

    public function __construct()
    {

    }

    public static function willRespond(EmailVerificationResponse $response)
    {
        $i           = new static;
        $i->response = $response;
        return $i;
    }

    public function execute(EmailVerificationRequest $request)
    {
        $this->calls[] = $request;

        return $this->response ?: EmailVerificationResponse::success($request->getEmail());
    }

    public function assertExecutedOnceWith(EmailVerificationRequest $request)
    {
        Assert::assertCount(1, $this->calls);
        Assert::assertEquals($request, $this->calls[0]);
    }
}
