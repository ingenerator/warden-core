<?php
/**
 * @author    Andrew Coulton <andrew@ingenerator.com>
 * @licence   proprietary
 */

namespace Ingenerator\Warden\Core\Support;


class NativePasswordHasher implements PasswordHasher
{

    /**
     * @var int
     */
    protected $algorithm;

    /**
     * @var array
     */
    protected $options;

    /**
     * @param array $config {'algorithm' => PASSWORD_DEFAULT, 'options' => { ...stuff... } }
     */
    public function __construct(array $config)
    {
        $this->algorithm = $config['algorithm'];
        $this->options   = $config['options'];
    }

    public function hash($password)
    {
        return \password_hash($password, $this->algorithm, $this->options);
    }

    public function isCorrect($password, $hash)
    {
        if ($hash === NULL) {
            return FALSE;
        }

        return \password_verify($password, $hash);
    }

    public function needsRehash($hash)
    {
        return \password_needs_rehash($hash, $this->algorithm, $this->options);
    }

}
