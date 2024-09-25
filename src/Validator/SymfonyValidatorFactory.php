<?php
/**
 * @author    Craig Gosman <craig@ingenerator.com>
 * @licence   proprietary
 */

namespace Ingenerator\Warden\Core\Validator;

use Symfony\Component\Validator\Validation;

class SymfonyValidatorFactory
{
    public static function factory(): SymfonyValidator
    {
        $builder = Validation::createValidatorBuilder();
        $builder->enableAttributeMapping();

        return new SymfonyValidator($builder->getValidator());
    }
}
