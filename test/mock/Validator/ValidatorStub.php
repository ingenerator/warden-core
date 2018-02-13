<?php
/**
 * @author    Andrew Coulton <andrew@ingenerator.com>
 * @licence   proprietary
 */
namespace test\mock\Ingenerator\Warden\Core\Validator;

use Ingenerator\Warden\Core\Validator\Validator;

class ValidatorStub implements Validator
{
    protected $result;
    protected $require_object;

    private function __construct()
    {

    }

    public static function alwaysValid()
    {
        $instance         = new static;
        $instance->result = [];

        return $instance;
    }

    public static function neverValid()
    {
        $instance         = new static;
        $instance->result = [
            'email' => 'Just nonsense',
        ];

        return $instance;
    }

    public static function validOnlyFor($object)
    {
        $instance                 = new static;
        $instance->require_object = $object;
        $instance->result         = [];

        return $instance;
    }

    public function validate($object)
    {
        if ($this->require_object AND ($this->require_object !== $object)) {
            return ['problem' => 'Unexpected object'];
        }

        return $this->result;
    }
}
