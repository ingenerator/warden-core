<?php
/**
 * @author    Craig Gosman <craig@ingenerator.com>
 * @licence   proprietary
 */

namespace test\integration\Ingenerator\Warden\Core\Validator\Symfony;


use Ingenerator\Warden\Core\Interactor\AbstractRequest;
use Ingenerator\Warden\Core\Interactor\ActivateAccountRequest;
use Ingenerator\Warden\Core\Interactor\ChangeEmailRequest;
use Ingenerator\Warden\Core\Interactor\ChangePasswordRequest;
use Ingenerator\Warden\Core\Interactor\EmailVerificationRequest;
use Ingenerator\Warden\Core\Interactor\LoginRequest;
use Ingenerator\Warden\Core\Interactor\PasswordResetRequest;
use Ingenerator\Warden\Core\Interactor\UserRegistrationRequest;
use Ingenerator\Warden\Core\Validator\SymfonyValidator;
use Ingenerator\Warden\Core\Validator\SymfonyValidatorFactory;
use Ingenerator\Warden\Core\Validator\Validator;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Constraints as Assert;

class SymfonyValidatorTest extends TestCase
{
    public function test_it_is_initialisable(): void
    {
        $subject = $this->newSubject();
        $this->assertInstanceOf(SymfonyValidator::class, $subject);
        $this->assertInstanceOf(Validator::class, $subject);
    }

    public static function providerValidatesInteractorRequests(): array
    {
        return [
            'ActivateAccountRequest is valid'                 => [
                ActivateAccountRequest::fromArray(['user_id' => '1234']),
                [],
            ],
            'ActivateAccountRequest user_id is not empty'     => [
                ActivateAccountRequest::fromArray(['user_id' => '']),
                ['user_id' => 'This value should not be blank.'],
            ],
            'ActivateAccountRequest user_id is a single word' => [
                ActivateAccountRequest::fromArray(['user_id' => 'foo bar']),
                ['user_id' => 'This value is not valid.'],
            ],

            'ChangeEmailRequest is valid'                     => [
                ChangeEmailRequest::fromArray(['user_id' => '1234', 'email' => 'foo@bar.com']),
                [],
            ],
            'ChangeEmailRequest values are not empty'         => [
                ChangeEmailRequest::fromArray(['user_id' => '', 'email' => '']),
                [
                    'user_id' => 'This value should not be blank.',
                    'email'   => 'This value should not be blank.',
                ],
            ],
            'ChangeEmailRequest user_id is a single word'     => [
                ChangeEmailRequest::fromArray(['user_id' => 'foo bar', 'email' => 'foo@bar.com']),
                ['user_id' => 'This value is not valid.'],
            ],
            'ChangeEmailRequest email is valid email address' => [
                ChangeEmailRequest::fromArray(['user_id' => '1234', 'email' => 'some nonsense']),
                ['email' => 'This value is not a valid email address.'],
            ],

            'ChangePasswordRequest is valid'                              => [
                ChangePasswordRequest::fromArray(['current_password' => '12345678', 'new_password' => '12345678']),
                [],
            ],
            'ChangePasswordRequest values are not blank'                  => [
                ChangePasswordRequest::fromArray(['current_password' => '', 'new_password' => '']),
                [
                    'current_password' => 'This value should not be blank.',
                    'new_password'     => 'This value should not be blank.',
                ],
            ],
            'ChangePasswordRequest new password is at least 8 chars long' => [
                ChangePasswordRequest::fromArray(['current_password' => '12345678', 'new_password' => '1234567']),
                ['new_password' => 'This value is too short. It should have 8 characters or more.'],
            ],

            'EmailVerificationRequest is valid'                     => [
                EmailVerificationRequest::fromArray(['email' => 'foo@bar.com']),
                [],
            ],
            'EmailVerificationRequest email is not blank'           => [
                EmailVerificationRequest::fromArray(['email' => '']),
                ['email' => 'This value should not be blank.'],
            ],
            'EmailVerificationRequest email is valid email address' => [
                EmailVerificationRequest::fromArray(['email' => 'some nonsense']),
                ['email' => 'This value is not a valid email address.'],
            ],

            'LoginRequest is valid'                     => [
                LoginRequest::fromArray(['email' => 'foo@bar.com']),
                [],
            ],
            'LoginRequest email is not blank'           => [
                LoginRequest::fromArray(['email' => '']),
                ['email' => 'This value should not be blank.'],
            ],
            'LoginRequest email is valid email address' => [
                LoginRequest::fromArray(['email' => 'some nonsense']),
                ['email' => 'This value is not a valid email address.'],
            ],

            'PasswordResetRequest is valid'                              => [
                PasswordResetRequest::fromArray(['user_id' => '1234', 'new_password' => '12345678']),
                [],
            ],
            'PasswordResetRequest values are not blank'                  => [
                PasswordResetRequest::fromArray(['user_id' => '', 'new_password' => '']),
                [
                    'user_id'      => 'This value should not be blank.',
                    'new_password' => 'This value should not be blank.',
                ],
            ],
            'PasswordResetRequest user_id is a single word'              => [
                PasswordResetRequest::fromArray(['user_id' => 'foo bar', 'new_password' => '12345678']),
                ['user_id' => 'This value is not valid.'],
            ],
            'PasswordResetRequest new password is at least 8 chars long' => [
                PasswordResetRequest::fromArray(['user_id' => '1234', 'new_password' => '1234567']),
                ['new_password' => 'This value is too short. It should have 8 characters or more.'],
            ],

            'UserRegistrationRequest is valid'                          => [
                UserRegistrationRequest::fromArray(['email' => 'foo@bar.com', 'password' => '12345678']),
                [],
            ],
            'UserRegistrationRequest values are not blank'              => [
                UserRegistrationRequest::fromArray(['email' => '', 'password' => '']),
                [
                    'email'    => 'This value should not be blank.',
                    'password' => 'This value should not be blank.',
                ],
            ],
            'UserRegistrationRequest password is at least 8 chars long' => [
                UserRegistrationRequest::fromArray(['email' => 'foo@bar.com', 'password' => '1234567']),
                ['password' => 'This value is too short. It should have 8 characters or more.'],
            ],
            'UserRegistrationRequest email is valid email address'      => [
                UserRegistrationRequest::fromArray(['email' => 'some nonsense', 'password' => '12345678']),
                ['email' => 'This value is not a valid email address.'],
            ],
        ];
    }

    /**
     * @dataProvider providerValidatesInteractorRequests
     */
    public function test_it_validates_interactor_requests(AbstractRequest $request, array $expect): void
    {
        $subject = $this->newSubject();
        $this->assertSame($expect, $subject->validate($request));
    }

    public static function providerValidationRulesInheritedByChildClass(): array
    {
        return [
            [
                ['email' => 'foo@bar.com', 'password' => '12345678', 'name' => 'Foo'],
                [],
            ],
            [
                ['email' => '', 'password' => '', 'name' => ''],
                [
                    'name'     => 'This value should not be blank.',
                    'email'    => 'This value should not be blank.',
                    'password' => 'This value should not be blank.',
                ],
            ],
        ];
    }

    /**
     * @dataProvider providerValidationRulesInheritedByChildClass
     */
    public function test_validation_rules_are_inherited_by_child_class(array $values, array $expect): void
    {
        $subject = $this->newSubject();
        $extended  = new class extends UserRegistrationRequest {
            #[Assert\NotBlank]
            protected string $name;

            public function __construct(){
            }
        };

        $this->assertSame($expect, $subject->validate($extended::fromArray($values)));
    }

    private function newSubject(): SymfonyValidator
    {
        return SymfonyValidatorFactory::factory();
    }

}
