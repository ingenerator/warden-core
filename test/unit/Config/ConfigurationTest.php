<?php
/**
 * @author    Andrew Coulton <andrew@ingenerator.com>
 * @licence   proprietary
 */

namespace test\unit\Ingenerator\Warden\Core\Config;


use Ingenerator\Warden\Core\Config\Configuration;
use Ingenerator\Warden\Core\Entity\SimpleUser;

class ConfigurationTest extends \PHPUnit\Framework\TestCase
{
    public function test_it_is_initialisable()
    {
        $this->assertInstanceOf('Ingenerator\Warden\Core\Config\Configuration', $this->newSubject());
    }

    public function test_it_provides_default_config_when_no_overrides_provided()
    {
        $subject = $this->newSubject([]);
        $this->assertSame(SimpleUser::class, $subject->getClassName('entity', 'user'));
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage No classmap entry for foo.bar
     */
    public function test_it_throws_on_attempt_to_map_undefined_class()
    {
        $this->newSubject()->getClassName('foo', 'bar');
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Class \Some\Unknown\Class mapped for entity.user is not defined
     */
    public function test_it_throws_if_class_mapped_to_nonexistent_class()
    {
        $this->newSubject(
            [
                'classmap' => ['entity' => ['user' => '\Some\Unknown\Class']],
            ]
        )->getClassName('entity', 'user');
    }

    public function test_it_returns_mapped_class_name_if_defined()
    {
        $subject = $this->newSubject(
            [
                'classmap' => ['entity' => ['user' => static::class]],
            ]
        );

        $this->assertSame(static::class, $subject->getClassName('entity', 'user'));
    }

    protected function newSubject(array $config_overrides = [])
    {
        return new Configuration($config_overrides);
    }


}
