<?php
/**
 * @author    Andrew Coulton <andrew@ingenerator.com>
 * @licence   proprietary
 */

namespace Ingenerator\Warden\Core\Support;


interface PasswordHasher
{

    /**
     * @param string $password
     *
     * @return string
     */
    public function hash($password);

    /**
     * @param string $password
     * @param string $hash
     *
     * @return boolean
     */
    public function isCorrect($password, $hash);

    /**
     * @param string $hash
     *
     * @return string
     */
    public function needsRehash($hash);

}
