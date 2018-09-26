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
use Ingenerator\Warden\Core\Repository\ArrayUserRepository;
use Ingenerator\Warden\Core\Repository\UserRepository;
use Ingenerator\Warden\Core\Support\PasswordHasher;
use Ingenerator\Warden\Core\UserSession\SimplePropertyUserSession;
use Ingenerator\Warden\Core\UserSession\UserSession;
use Ingenerator\Warden\Core\Validator\Validator;
use test\mock\Ingenerator\Warden\Core\Entity\UserStub;
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
        $this->assertInstanceOf('\Ingenerator\Warden\Core\Interactor\LoginInteractor', $this->newSubject());
    }

    /**
     * @expectedException \LogicException
     */
    public function test_it_throws_if_user_already_logged_in()
    {
        $this->user_session->login(new SimpleUser);
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
            [UserStub::activeWithPasswordHash('foo@bar.com', 'abcdefgh'), LoginResponse::ERROR_PASSWORD_INCORRECT],
            [UserStub::inactiveWithPasswordHash('foo@bar.com', 'abcdefgh'), LoginResponse::ERROR_NOT_ACTIVE],
            [UserStub::inactiveWithPasswordHash('foo@bar.com', '87654321'), LoginResponse::ERROR_NOT_ACTIVE],
        ];
    }

    /**
     * @dataProvider provider_failed_login_users
     */
    public function test_it_fails_if_user_password_is_invalid_or_user_inactive(User $user, $expect_code)
    {
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
    public function test_it_succeeds_with_correct_password_and_email_in_any_case($actual_email, $entered_email)
    {
        $this->password_hasher = new ReversingPassswordHasherStub();
        $user                  = UserStub::activeWithPasswordHash($actual_email, '12345678');
        $this->user_repo->save($user);
        $response = $this->executeWith(['email' => $entered_email, 'password' => '87654321']);
        $this->assertSuccessful($response);
        $this->assertSame($user, $response->getUser());
    }

    public function test_it_logs_in_user_in_session_on_successful_login()
    {
        $this->password_hasher = new ReversingPassswordHasherStub();
        $user                  = UserStub::activeWithPasswordHash('foo@bar.com', '12345678');
        $this->user_repo->save($user);
        $this->executeWith(['email' => 'foo@bar.com', 'password' => '87654321']);
        $this->assertTrue($this->user_session->isAuthenticated(), 'User session should be authenticated');
        $this->assertSame($user, $this->user_session->getUser(), 'User session should be authenticated');
    }

    public function test_it_upgrades_password_hash_on_successful_login_if_required()
    {
        $this->password_hasher = ReversingPassswordHasherStub::withRehashNeeded();
        $user                  = UserStub::activeWithPasswordHash('foo@bar.com', '12345678');
        $this->user_repo       = new SaveSpyingUserRepository([$user]);
        $this->executeWith(['email' => 'foo@bar.com', 'password' => '87654321']);
        $this->user_repo->assertOneSaved($user);
    }

    public function test_it_does_not_change_password_hash_on_successful_login_if_current_hash_is_secure()
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

    public function test_it_does_not_send_any_user_notification_on_succesful_login()
    {
        $this->email_verification = $this->getMockExpectingNoCalls(EmailVerificationInteractor::class);
        $this->test_it_succeeds_with_correct_password_and_email_in_any_case('foo@bar.com', 'foo@bar.com');
    }

    public function test_it_sends_user_notification_with_activation_url_on_inactive_user()
    {
        $this->markTestIncomplete();
    }

    public function test_it_triggers_email_verification_for_password_reset_on_incorrect_password()
    {
        $this->email_verification = new EmailVerificationInteractorSpy;
        $user = UserStub::activeWithPasswordHash('foo@bar.com', '12345678');
        $this->user_repo->save($user);
        $this->executeWith(['email' => 'foo@bar.com', 'password' => 'wrong']);
        $this->email_verification->assertExecutedOnceWith(EmailVerificationRequest::forPasswordReset($user));
    }

    public function test_its_password_reset_token_is_only_valid_until_the_current_password_is_changed()
    {
        $this->markTestIncomplete();
    }

    public function setUp()
    {
        parent::setUp();
        $this->email_verification = new EmailVerificationInteractorSpy;
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
            $this->user_repo,
            $this->password_hasher,
            $this->user_session,
            $this->email_verification
        );
    }

    /**
     * @param string $className
     *
     * @return \PHPUnit_Framework_MockObject_MockObject
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

    public function __construct()
    {

    }

    public function execute(EmailVerificationRequest $request)
    {
        $this->calls[] = $request;

        return EmailVerificationResponse::success($request->getEmail());
    }

    public function assertExecutedOnceWith(EmailVerificationRequest $request)
    {
        \PHPUnit_Framework_Assert::assertCount(1, $this->calls);
        \PHPUnit_Framework_Assert::assertEquals($request, $this->calls[0]);
    }
}
