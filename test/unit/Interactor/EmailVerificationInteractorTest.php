<?php
/**
 * @author    Andrew Coulton <andrew@ingenerator.com>
 * @licence   proprietary
 */

namespace test\unit\Ingenerator\Warden\Core\Interactor;


use Ingenerator\Warden\Core\Interactor\EmailVerificationInteractor;
use Ingenerator\Warden\Core\Interactor\EmailVerificationRequest;
use Ingenerator\Warden\Core\Interactor\EmailVerificationResponse;
use Ingenerator\Warden\Core\Notification\ConfirmationRequiredNotification;
use Ingenerator\Warden\Core\Notification\UserNotification;
use Ingenerator\Warden\Core\Repository\ArrayUserRepository;
use Ingenerator\Warden\Core\Repository\UserRepository;
use Ingenerator\Warden\Core\Support\EmailConfirmationTokenService;
use Ingenerator\Warden\Core\Support\UrlProvider;
use test\mock\Ingenerator\Warden\Core\Entity\UserStub;
use test\mock\Ingenerator\Warden\Core\Support\InsecureJSONTokenServiceStub;
use test\mock\Ingenerator\Warden\Core\Support\UserNotificationMailerSpy;
use test\mock\Ingenerator\Warden\Core\Validator\ValidatorStub;

class EmailVerificationInteractorTest extends AbstractInteractorTest
{
    /**
     * @var EmailConfirmationTokenService
     */
    protected $email_token_service;
    /**
     * @var UrlProvider
     */
    protected $url_provider;

    /**
     * @var UserRepository
     */
    protected $user_repository;

    /**
     * @var ValidatorStub
     */
    protected $validator;

    /**
     * @var UserNotificationMailerSpy
     */
    protected $user_notification;

    public function test_it_is_initialisable()
    {
        $this->assertInstanceOf('Ingenerator\Warden\Core\Interactor\EmailVerificationInteractor', $this->newSubject());
    }

    public function test_it_fails_if_request_is_invalid()
    {
        $this->validator = ValidatorStub::neverValid();
        $this->executeWith([]);
        $this->assertFailsWithCode(EmailVerificationResponse::ERROR_DETAILS_INVALID, $this->executeWith([]));
    }

    public function test_it_succeeds_if_request_is_valid()
    {
        $request         = EmailVerificationRequest::forRegistration('foo@bar.com');
        $this->validator = ValidatorStub::validOnlyFor($request);
        $this->assertSuccessful($this->newSubject()->execute($request));
        $this->assertSame('foo@bar.com', $request->getEmail());
    }
    
    public function test_it_fails_if_requesting_register_verification_for_existing_user()
    {
        $this->user_repository->save(UserStub::withEmail('foo@bar.com'));
        $result = $this->newSubject()->execute(EmailVerificationRequest::forRegistration('foo@bar.com'));
        $this->assertFailsWithCode(
            EmailVerificationResponse::ERROR_ALREADY_REGISTERED,
            $result
        );
        $this->assertSame('foo@bar.com', $result->getEmail());
    }

    public function test_it_sends_user_notification_to_requested_email()
    {
        $this->executeWith(EmailVerificationRequest::forRegistration('foo@bar.com'));

        return $this->user_notification->assertSentOne(ConfirmationRequiredNotification::class, 'foo@bar.com');
    }

    /**
     * @depends test_it_sends_user_notification_to_requested_email
     */
    public function test_it_populates_reason_in_user_notification(ConfirmationRequiredNotification $notification)
    {
        $this->assertEquals(EmailVerificationRequest::REGISTER, $notification->getAction());
    }

    public function test_it_provides_signed_continuation_url_for_registration()
    {
        $this->url_provider        = new FixedUrlProviderStub('/auth');
        $this->email_token_service = new InsecureJSONTokenServiceStub;
        $this->executeWith(EmailVerificationRequest::forRegistration('foo@bar.com'));

        $this->assertContinuationUrlAndQuery(
            $this->url_provider->getRegistrationUrl(),
            [
                'action' => EmailVerificationRequest::REGISTER,
                'email'  => 'foo@bar.com',
                'token'  => '{"action":"register","email":"foo@bar.com"}',
            ],
            $this->user_notification->getFirstNotification()
        );
    }

    public function test_it_provides_signed_continuation_url_for_password_reset()
    {
        $this->url_provider        = new FixedUrlProviderStub('/auth');
        $this->email_token_service = new InsecureJSONTokenServiceStub;
        $user                      = UserStub::activeWithPasswordHash('foo@bar.com', 'hashyhash');
        $this->executeWith(EmailVerificationRequest::forPasswordReset($user));

        $this->assertContinuationUrlAndQuery(
            $this->url_provider->getLoginUrl(),
            [
                'action' => EmailVerificationRequest::RESET_PASSWORD,
                'email'  => 'foo@bar.com',
                'token'  => '{"action":"reset-password","email":"foo@bar.com","current_pw_hash":"hashyhash"}',
            ],
            $this->user_notification->getFirstNotification()
        );
    }

    public function setUp()
    {
        parent::setUp();
        $this->email_token_service = new InsecureJSONTokenServiceStub;
        $this->url_provider        = new FixedUrlProviderStub;
        $this->user_repository     = new ArrayUserRepository;
        $this->user_notification   = new UserNotificationMailerSpy;
        $this->validator           = ValidatorStub::alwaysValid();
    }

    /**
     * @param EmailVerificationRequest|array $request
     *
     * @return EmailVerificationResponse
     */
    protected function executeWith($request)
    {
        if ( ! $request instanceof EmailVerificationRequest) {
            $request = EmailVerificationRequest::fromArray($request);
        }

        return $this->newSubject()->execute($request);
    }

    protected function newSubject()
    {
        return new EmailVerificationInteractor(
            $this->validator,
            $this->user_repository,
            $this->email_token_service,
            $this->url_provider,
            $this->user_notification
        );
    }

    /**
     * @param string           $expect_url
     * @param array            $expect_query
     * @param UserNotification $notification
     */
    protected function assertContinuationUrlAndQuery(
        $expect_url,
        array $expect_query,
        UserNotification $notification
    ) {
        if ( ! $notification instanceof ConfirmationRequiredNotification) {
            throw new \InvalidArgumentException(
                'Expected ConfirmationRequiredNotification, got '.get_class($notification)
            );
        }
        $url = strtok($notification->getContinuationUrl(), '?');
        $this->assertEquals($expect_url, $url);
        $query = parse_url($notification->getContinuationUrl(), PHP_URL_QUERY);
        parse_str($query, $query_parts);
        $this->assertEquals(
            $expect_query,
            $query_parts
        );
    }

}


class FixedUrlProviderStub implements UrlProvider
{
    protected $base_url;

    public function __construct($base_url = '/foo')
    {
        $this->base_url = '/foo';
    }

    public function getLoginUrl()
    {
        return $this->base_url.'/login';
    }

    public function getRegistrationUrl()
    {
        return $this->base_url.'/register';
    }

}
