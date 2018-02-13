<?php
/**
 * @author    Andrew Coulton <andrew@ingenerator.com>
 * @licence   proprietary
 */

namespace test\unit\Ingenerator\Warden\Core\Support;


use Ingenerator\Warden\Core\Support\NativePasswordHasher;

class NativePasswordHasherTest extends \PHPUnit_Framework_TestCase
{
    protected $config = [
        'algorithm' => PASSWORD_DEFAULT,
        'options'   => [
            'cost' => 10,
        ],
    ];

    public function test_it_is_initialisable()
    {
        $subject = $this->newSubject();
        $this->assertInstanceOf('Ingenerator\Warden\Core\Support\NativePasswordHasher', $subject);
        $this->assertInstanceOf('Ingenerator\Warden\Core\Support\PasswordHasher', $subject);
    }

    public function test_it_hashes_password()
    {
        $this->assertNotEmpty($this->newSubject()->hash('foobar'));
    }

    public function test_it_hashes_password_with_configured_options()
    {
        $this->config['algorithm']       = PASSWORD_BCRYPT;
        $this->config['options']['cost'] = 8;

        $subject = $this->newSubject();
        $info    = password_get_info($subject->hash('12345678'));
        $this->assertEquals(['algo' => PASSWORD_BCRYPT, 'algoName' => 'bcrypt', 'options' => ['cost' => 8]], $info);
    }

    /**
     * @testWith ["my password", "some other password", false]
     *           ["my password", "my password", true]
     */
    public function test_it_can_verify_hashed_password($orig_password, $verify_password, $expect_correct)
    {
        $subject = $this->newSubject();
        $hash    = $subject->hash($orig_password);
        $this->assertSame($expect_correct, $subject->isCorrect($verify_password, $hash));
    }

    public function test_it_can_verify_old_hashed_password_even_when_configuration_has_changed()
    {
        $password                        = '12346689';
        $this->config['options']['cost'] = 9;
        $hash                            = $this->newSubject()->hash($password);
        $this->config['options']['cost'] = 11;
        $this->assertTrue($this->newSubject()->isCorrect($password, $hash));
    }

    public function test_password_does_not_need_upgrading_when_configuration_unchanged()
    {
        $subject = $this->newSubject();
        $hash    = $subject->hash('any passw0rd');
        $this->assertFalse($subject->needsRehash($hash));
    }

    public function test_password_needs_upgrading_when_configuration_changed_since_generation()
    {
        $password                        = '12346689';
        $this->config['options']['cost'] = 9;
        $hash                            = $this->newSubject()->hash($password);
        $this->config['options']['cost'] = 11;
        $this->assertTrue($this->newSubject()->needsRehash($hash));
    }

    protected function newSubject()
    {
        return new NativePasswordHasher($this->config);
    }

}
