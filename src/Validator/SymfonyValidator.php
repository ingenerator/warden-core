<?php
/**
 * @author    Andrew Coulton <andrew@ingenerator.com>
 * @licence   proprietary
 */

namespace Ingenerator\Warden\Core\Validator;

use Symfony\Component\Validator\ConstraintViolationInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class SymfonyValidator implements Validator
{

    public function __construct(protected ValidatorInterface $validator)
    {
    }

    public function validate($object): array
    {
        $errors         = $this->validator->validate($object);
        $error_messages = [];
        foreach ($errors as $error) {
            /** @var ConstraintViolationInterface $error */
            $error_messages[$error->getPropertyPath()] = $error->getMessage();
        }

        return $error_messages;
    }
}
