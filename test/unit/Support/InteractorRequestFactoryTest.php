<?php
/**
 * @author    Andrew Coulton <andrew@ingenerator.com>
 * @licence   proprietary
 */

namespace test\unit\Ingenerator\Warden\Core\Support;


use Ingenerator\Warden\Core\Config\Configuration;
use Ingenerator\Warden\Core\Interactor\EmailVerificationRequest;
use Ingenerator\Warden\Core\Support\InteractorRequestFactory;

class InteractorRequestFactoryTest extends \PHPUnit\Framework\TestCase
{

    protected $config;

    public function test_it_is_initialisable()
    {
        $this->assertInstanceOf('Ingenerator\Warden\Core\Support\InteractorRequestFactory', $this->newSubject());
    }

    public function test_it_can_factory_standard_request_objects()
    {
        $this->config = new Configuration(
            [
                'classmap' => [
                    'interactor_request' => [
                        'email_verification' => EmailVerificationRequest::class,
                    ],
                ],
            ]
        );
        $request      = $this->newSubject()->make('email_verification', 'forRegistration', 'foo@bar.com');
        /** @var EmailVerificationRequest $request */
        $this->assertInstanceOf(EmailVerificationRequest::class, $request);
        $this->assertSame('foo@bar.com', $request->getEmail());
    }

    public function test_it_can_factory_custom_request_objects()
    {
        $this->config = new Configuration(
            [
                'classmap' => [
                    'interactor_request' => [
                        'email_verification' => CustomRequest::class,
                    ],
                ],
            ]
        );
        $request      = $this->newSubject()->make('email_verification', 'forRegistration', 'foo@bar.com');
        /** @var EmailVerificationRequest $request */
        $this->assertInstanceOf(CustomRequest::class, $request);
        $this->assertSame('foo@bar.com', $request->getEmail());
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->config = new Configuration([]);
    }

    protected function newSubject()
    {
        return new InteractorRequestFactory($this->config);
    }

}

class CustomRequest extends EmailVerificationRequest
{

}
