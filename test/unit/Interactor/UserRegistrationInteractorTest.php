<?php
/**
 * @author    Andrew Coulton <andrew@ingenerator.com>
 * @licence   proprietary
 */

namespace test\unit\Ingenerator\Warden\Core\Interactor;


use Ingenerator\Warden\Core\Config\Configuration;
use Ingenerator\Warden\Core\Entity\User;
use Ingenerator\Warden\Core\Interactor\EmailVerificationRequest;
use Ingenerator\Warden\Core\Interactor\UserRegistrationInteractor;
use Ingenerator\Warden\Core\Interactor\UserRegistrationRequest;
use Ingenerator\Warden\Core\Interactor\UserRegistrationResponse;
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

class UserRegistrationInteractorTest extends AbstractInteractorTest
{
    protected $config = [
        'registration' => [
            'require_confirmed_email' => FALSE,
        ],
    ];

    /**
     * @var \test\mock\Ingenerator\Warden\Core\Support\ReversingPassswordHasherStub
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
        $this->assertInstanceOf('Ingenerator\Warden\Core\Interactor\UserRegistrationInteractor', $this->newSubject());
    }

    public function test_it_fails_if_details_are_not_valid()
    {
        $this->validator = ValidatorStub::neverValid();
        $this->assertFailsWithCode(UserRegistrationResponse::ERROR_DETAILS_INVALID, $this->executeWith([]));
    }

    public function test_it_fails_if_invalid_email_confirmation_token_presented()
    {
        $this->assertFailsWithCode(
            UserRegistrationResponse::ERROR_EMAIL_CONFIRMATION_INVALID,
            $this->executeWith(
                [
                    'email'                    => 'some.tampered@email.net',
                    'email_confirmation_token' => $this->givenValidToken('any.where@some.net'),
                ]
            )
        );
    }

    /**
     * @testWith ["foo@bar.com", "foo@bar.com"]
     *           ["foo@bar.com", "foO@BAR.com"]
     *           ["foo@bar.com", " foo@bar.com "]
     */
    public function test_it_fails_if_user_email_already_has_account($existing_email, $new_email)
    {
        $this->user_repo->save(UserStub::withEmail($existing_email));

        $result = $this->executeWith(['email' => $new_email]);
        $this->assertFailsWithCode(UserRegistrationResponse::ERROR_ALREADY_REGISTERED, $result);
        $this->assertSame($existing_email, $result->getEmail());
    }

    public function test_it_succeeds_if_details_are_valid()
    {
        $request         = UserRegistrationRequest::fromArray([]);
        $this->validator = ValidatorStub::validOnlyFor($request);
        $this->assertSuccessful($this->newSubject()->execute($request));
    }

    public function test_it_hashes_user_password_for_storage()
    {
        $this->password_hasher = new ReversingPassswordHasherStub;
        $result                = $this->executeWith(['password' => '12345678']);
        $this->assertSame('87654321', $result->getUser()->getPasswordHash());
    }

    public function test_it_allows_customised_request_to_assign_additional_user_properties()
    {
        $request = CustomRegistrationRequest::fromArray(
            [
                'email'       => 'foo@boo.net',
                'extra_field' => 'The custom request maps this as a public user property',
            ]
        );
        $result  = $this->newSubject()->execute($request);
        $this->assertSame('The custom request maps this as a public user property', $result->getUser()->extra_field);
    }

    public function test_it_creates_inactive_if_email_unconfirmed_when_configured_to_allow_inactive_users()
    {
        $this->config['registration']['require_confirmed_email'] = FALSE;

        $result = $this->executeWith(['email' => 'foo@bar.com', 'email_confirmation_token' => NULL]);
        $this->assertFalse($result->getUser()->isActive());
    }

    public function test_it_fails_if_email_unconfirmed_when_configured_to_require_active_users()
    {
        $this->config['registration']['require_confirmed_email'] = TRUE;

        $result = $this->executeWith(['email' => 'foo@bar.com', 'email_confirmation_token' => NULL]);
        $this->assertFailsWithCode(UserRegistrationResponse::ERROR_EMAIL_UNCONFIRMED, $result);
    }

    public function test_it_creates_active_user_if_valid_email_confirmation_token_provided()
    {
        $result = $this->executeWith(
            [
                'email'                    => 'foo@bar.net',
                'email_confirmation_token' => $this->givenValidToken('foo@bar.net'),
            ]
        );
        $this->assertTrue($result->getUser()->isActive());
    }

    public function test_it_saves_new_user()
    {
        $result = $this->executeWith(['email' => 'anyone@anywhere.net']);
        $this->assertSame($result->getUser(), $this->user_repo->findByEmail('anyone@anywhere.net'));
    }

    public function test_it_logs_in_user_when_active_on_registration()
    {
        $result = $this->executeWith(
            [
                'email'                    => 'foo@bar.net',
                'email_confirmation_token' => $this->givenValidToken('foo@bar.net'),
            ]
        );
        $this->assertSame($result->getUser(), $this->user_session->getUser());
    }

    public function test_it_does_not_log_in_user_when_inactive_on_registration()
    {
        $this->executeWith(['email' => 'foo@bar.com', 'email_confirmation_token' => NULL]);
        $this->assertFalse($this->user_session->isAuthenticated());
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
        return new UserRegistrationInteractor(
            new Configuration($this->config),
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
     * @return UserRegistrationResponse
     */
    protected function executeWith(array $details)
    {
        return $this->newSubject()->execute(UserRegistrationRequest::fromArray($details));
    }

    /**
     * @param $email
     *
     * @return mixed
     */
    protected function givenValidToken($email)
    {
        return $this->email_token_service->createToken(
            [
                'action' => EmailVerificationRequest::REGISTER,
                'email'  => $email,
            ]
        );
    }

}

class CustomRegistrationRequest extends UserRegistrationRequest
{
    protected $extra_field;

    public function populateExtraFields(User $user)
    {
        $user->extra_field = $this->extra_field;
    }
}
