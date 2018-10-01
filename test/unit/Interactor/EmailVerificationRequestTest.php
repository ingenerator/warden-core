<?php
/**
 * @author    Andrew Coulton <andrew@ingenerator.com>
 * @licence   proprietary
 */

namespace test\unit\Ingenerator\Warden\Core\Interactor;


use Ingenerator\Warden\Core\Interactor\EmailVerificationRequest;
use Ingenerator\Warden\Core\Support\FixedUrlProviderStub;
use PHPUnit\Framework\TestCase;
use test\mock\Ingenerator\Warden\Core\Entity\UserStub;

class EmailVerificationRequestTest extends TestCase
{

    public function provider_requires_unregistered_email()
    {
        return [
            [EmailVerificationRequest::CHANGE_EMAIL, TRUE],
            [EmailVerificationRequest::NEW_USER_INVITE, FALSE],
            [EmailVerificationRequest::REGISTER, TRUE],
            [EmailVerificationRequest::RESET_PASSWORD, FALSE],
        ];
    }

    /**
     * @dataProvider provider_requires_unregistered_email
     */
    public function test_it_indicates_whether_it_requires_unregistered_email($type, $expect)
    {
        $this->assertSame($expect, $this->makeRequest($type)->requiresUnregisteredEmail());
    }

    public function provider_url_parameters()
    {
        return [
            [
                EmailVerificationRequest::CHANGE_EMAIL,
                [UserStub::fromArray(['id' => 122, 'email' => 'old@bar.com']), 'new@bar.com'],
                [
                    'email'   => 'new@bar.com',
                    'token'   => [
                        'email'         => 'new@bar.com',
                        'action'        => EmailVerificationRequest::CHANGE_EMAIL,
                        'user_id'       => 122,
                        'current_email' => 'old@bar.com'
                    ],
                    'user_id' => 122,
                ],
            ],
            [
                EmailVerificationRequest::NEW_USER_INVITE,
                [UserStub::fromArray(['id' => 122, 'email' => 'foo@bar.com'])],
                [
                    'email' => 'foo@bar.com',
                    'token' => [
                        'email'           => 'foo@bar.com',
                        'action'          => EmailVerificationRequest::RESET_PASSWORD,
                        'current_pw_hash' => NULL
                    ]
                ],
            ],
            [
                EmailVerificationRequest::REGISTER,
                ['foo@bar.com'],
                [
                    'email' => 'foo@bar.com',
                    'token' => [
                        'email'  => 'foo@bar.com',
                        'action' => EmailVerificationRequest::REGISTER,
                    ],
                ]
            ],
            [
                EmailVerificationRequest::RESET_PASSWORD,
                [UserStub::activeWithPasswordHash('foo@bar.com', 'abcde')],
                [
                    'email' => 'foo@bar.com',
                    'token' => [
                        'email'           => 'foo@bar.com',
                        'action'          => EmailVerificationRequest::RESET_PASSWORD,
                        'current_pw_hash' => 'abcde'
                    ]
                ],
            ],
        ];
    }

    /**
     * @dataProvider provider_url_parameters
     */
    public function test_it_provides_url_and_token_parameters($type, $type_args, $expect)
    {
        $this->assertSame($expect, $this->makeRequest($type, $type_args)->getUrlParamsToSign());
    }

    public function provider_continuation_url()
    {
        return [
            [EmailVerificationRequest::CHANGE_EMAIL, '/complete-change-email?any=thing'],
            [EmailVerificationRequest::NEW_USER_INVITE, '/complete-password-reset?any=thing'],
            [EmailVerificationRequest::REGISTER, '/complete-registration?any=thing'],
            [EmailVerificationRequest::RESET_PASSWORD, '/complete-password-reset?any=thing'],
        ];
    }

    /**
     * @dataProvider provider_continuation_url
     */
    public function test_it_provides_continuation_url_from_url_provider($type, $expect)
    {
        $urls = new FixedUrlProviderStub;
        $this->assertSame(
            $expect,
            $this->makeRequest($type)->getContinuationUrl($urls, ['any' => 'thing'])
        );
    }

    /**
     * @param string $type
     * @param array  $args
     *
     * @return \Ingenerator\Warden\Core\Interactor\EmailVerificationRequest
     */
    protected function makeRequest($type, $args = [])
    {
        switch ($type) {
            case EmailVerificationRequest::CHANGE_EMAIL:
                $args = array_merge($args, [UserStub::withEmail('anyone@td.com'), 'new@td.com']);
                return EmailVerificationRequest::forChangeEmail($args[0], $args[1]);

            case EmailVerificationRequest::NEW_USER_INVITE:
                $args = array_merge($args, [UserStub::withEmail('anyone@td.com')]);
                return EmailVerificationRequest::forNewUserInvite($args[0]);

            case EmailVerificationRequest::REGISTER:
                $args = array_merge($args, ['anyone@td.com']);
                return EmailVerificationRequest::forRegistration($args[0]);

            case EmailVerificationRequest::RESET_PASSWORD:
                $args = array_merge($args, [UserStub::withEmail('anyone@td.com')]);
                return EmailVerificationRequest::forPasswordReset($args[0]);

            default:
                throw new \InvalidArgumentException('Can\'t factory type '.$type);
        }
    }

}
