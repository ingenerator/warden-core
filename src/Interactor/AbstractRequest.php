<?php
/**
 * @author    Andrew Coulton <andrew@ingenerator.com>
 * @licence   proprietary
 */

namespace Ingenerator\Warden\Core\Interactor;


abstract class AbstractRequest
{
    private function __construct()
    {

    }

    public static function fromArray(array $data)
    {
        $instance = new static;
        foreach ($data as $field => $value) {
            // Sanitise all email addresses coming in to ensure that there are no mixed-case issues for users
            if ($field === 'email') {
                $value = \trim(\strtolower((string) $value));
            }
            $instance->$field = $value;
        }

        return $instance;
    }

}
