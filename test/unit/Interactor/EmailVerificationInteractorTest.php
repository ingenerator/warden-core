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
use Ingenerator\Warden\Core\RateLimit\LeakyBucket;
use Ingenerator\Warden\Core\RateLimit\LeakyBucketStatus;
use Ingenerator\Warden\Core\Repository\ArrayUserRepository;
use Ingenerator\Warden\Core\Repository\UserRepository;
use Ingenerator\Warden\Core\Support\EmailConfirmationTokenService;
use Ingenerator\Warden\Core\Support\FixedUrlProviderStub;
use test\mock\Ingenerator\Warden\Core\Entity\UserStub;
use test\mock\Ingenerator\Warden\Core\RateLimit\LeakyBucketStub;
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
     * @var LeakyBucket
     */
    protected $leaky_bucket;

    /**
     * @var FixedUrlProviderStub
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
        $this->assertInstanceOf(EmailVerificationInteractor::class, $this->newSubject());
    }

    public function test_it_fails_if_request_is_invalid()
    {
        $this->validator = ValidatorStub::neverValid();
        $this->executeWith([]);
        $this->assertFailsWithCode(
            EmailVerificationResponse::ERROR_DETAILS_INVALID,
            $this->executeWith([])
        );
    }

    public function test_it_succeeds_if_request_is_valid()
    {
        $request         = EmailVerificationRequest::forRegistration('foo@bar.com');
        $this->validator = ValidatorStub::validOnlyFor($request);
        $this->assertSuccessful($this->newSubject()->execute($request));
        $this->assertSame('foo@bar.com', $request->getEmail());
    }

    public function test_it_fails_if_requesting_verification_for_existing_email_when_required()
    {
        $this->user_repository->save(UserStub::withEmail('foo@bar.com'));
        $result = $this->newSubject()->execute(
            EmailVerificationRequest::forRegistration('foo@bar.com')
        );
        $this->assertFailsWithCode(
            EmailVerificationResponse::ERROR_ALREADY_REGISTERED,
            $result
        );
        $this->assertSame('foo@bar.com', $result->getEmail());
    }

    public function test_it_sends_user_notification_to_requested_email()
    {
        $this->executeWith(EmailVerificationRequest::forRegistration('foo@bar.com'));

        return $this->user_notification->assertSentOne(
            ConfirmationRequiredNotification::class,
            'foo@bar.com'
        );
    }

    /**
     * @depends test_it_sends_user_notification_to_requested_email
     */
    public function test_it_populates_reason_in_user_notification(
        ConfirmationRequiredNotification $notification
    ) {
        $this->assertEquals(EmailVerificationRequest::REGISTER, $notification->getAction());
    }

    public function test_it_provides_signed_continuation_url_based_on_request()
    {
        $this->url_provider        = new FixedUrlProviderStub;
        $this->email_token_service = new InsecureJSONTokenServiceStub;
        $this->executeWith(EmailVerificationRequest::forRegistration('foo@bar.com'));

        $this->assertContinuationUrlAndQuery(
            '/complete-registration',
            [
                'email' => 'foo@bar.com',
                'token' => '{"action":"register","email":"foo@bar.com"}',
            ],
            $this->user_notification->getFirstNotification()
        );
    }

    public function provider_user_in_notification()
    {
        $user = UserStub::withEmail('foo@bar.com');
        return [
            [
                EmailVerificationRequest::forRegistration('foo@bar.com'),
                FALSE
            ],
            [
                EmailVerificationRequest::forPasswordReset($user),
                $user
            ]
        ];
    }

    /**
     * @dataProvider provider_user_in_notification
     */
    public function test_it_populates_user_in_notification_if_present_in_request(
        $request,
        $expect_user
    ) {
        $this->executeWith($request);
        $notification = $this->user_notification->getFirstNotification();
        /** @var ConfirmationRequiredNotification $notification */
        if ($expect_user === FALSE) {
            $this->assertFalse($notification->hasRecipientUser());
        } else {
            $this->assertSame($expect_user, $notification->getRecipientUser());
        }
    }

    public function email_leaky_bucket_request_id()
    {
        return [
            [
                EmailVerificationRequest::forRegistration('foo@bar.com'),
                'warden.email.register',
                'foo@bar.com'
            ],
            [
                EmailVerificationRequest::forPasswordReset(UserStub::withEmail('reset@bar.com')),
                'warden.email.reset-password',
                'reset@bar.com'
            ],
        ];
    }

    /**
     * @dataProvider email_leaky_bucket_request_id
     */
    public function test_it_rate_limits_by_email_type_and_recipient($request, $expect_type, $expect_requester)
    {
        $this->leaky_bucket = LeakyBucketStub::alwaysResponds(LeakyBucketStatus::allowed());
        $this->executeWith($request);
        $this->leaky_bucket->assertOnlyRequestedOnce($expect_type, $expect_requester);
    }

    public function test_it_returns_rate_limited_response_without_sending_if_rate_limit_exceeded()
    {
        $next_available     = new \DateTimeImmutable('next monday');
        $this->leaky_bucket = LeakyBucketStub::alwaysResponds(
            LeakyBucketStatus::rateLimited($next_available)
        );
        $result             = $this->executeWith(
            EmailVerificationRequest::forRegistration('foo@bar.com')
        );
        $this->assertFailsWithCode(
            EmailVerificationResponse::ERROR_RATE_LIMITED,
            $result
        );
        $this->assertSame('foo@bar.com', $result->getEmail());
        $this->assertSame($next_available, $result->canRetryAfter());
        $this->user_notification->assertNothingSent();
    }

    public function setUp()
    {
        parent::setUp();
        $this->email_token_service = new InsecureJSONTokenServiceStub;
        $this->leaky_bucket        = LeakyBucketStub::alwaysResponds(LeakyBucketStatus::allowed());
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
            $this->leaky_bucket,
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
