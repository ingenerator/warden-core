<?php
/**
 * @author    Andrew Coulton <andrew@ingenerator.com>
 * @licence   proprietary
 */

namespace Ingenerator\Warden\Core\Interactor;


class AbstractResponse
{
    const ERROR_DETAILS_INVALID = 'details-invalid';

    /**
     * @var string
     */
    protected $failure_code;

    /**
     * @var array
     */
    protected $validation_errors;

    /**
     * @var boolean
     */
    protected $was_success;

    /**
     * @param boolean $success
     * @param string  $failure_code
     */
    protected function __construct($success, $failure_code = NULL)
    {
        $this->failure_code = $failure_code;
        $this->was_success  = $success;
    }

    public static function validationFailed($errors)
    {
        //@todo: include errors in response
        $instance                    = new static(FALSE, static::ERROR_DETAILS_INVALID);
        $instance->validation_errors = $errors;
        return $instance;
    }

    /**
     * @return string
     */
    public function getFailureCode()
    {
        return $this->failure_code;
    }

    /**
     * @param string $code
     *
     * @return bool
     */
    public function isFailureCode($code)
    {
        return ($this->failure_code === $code);
    }

    public function getValidationErrors()
    {
        if ( ! $this->isFailureCode(static::ERROR_DETAILS_INVALID)) {
            throw new \BadMethodCallException('Cannot access validation errors of valid '.\get_class($this));
        }
        return $this->validation_errors;
    }

    /**
     * @return boolean
     */
    public function wasSuccessful()
    {
        return $this->was_success;
    }
}
