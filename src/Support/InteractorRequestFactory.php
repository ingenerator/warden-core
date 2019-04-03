<?php
/**
 * @author    Andrew Coulton <andrew@ingenerator.com>
 * @licence   proprietary
 */

namespace Ingenerator\Warden\Core\Support;


use Ingenerator\Warden\Core\Config\Configuration;

class InteractorRequestFactory
{
    /**
     * @var Configuration
     */
    protected $config;

    public function __construct(Configuration $config)
    {
        $this->config = $config;
    }

    /**
     * @param string $type
     * @param string $factory_method
     * @param string $argument
     *
     * @return object
     */
    public function make($type, $factory_method, $argument)
    {
        // @todo: this factorying feels icky and breaks typehinting and completion, isn't there a better way?
        $class = $this->config->getClassName('interactor_request', $type);

        return \call_user_func("$class::$factory_method", $argument);
    }
}
