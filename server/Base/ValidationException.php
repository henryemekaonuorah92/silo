<?php

namespace Silo\Base;

use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationListInterface;

class ValidationException extends \Exception
{
    protected $violations;

    public function __construct(ConstraintViolationListInterface $violations)
    {
        $this->violations = $violations;

        $strs = [];
        foreach ($this->violations as $violation) {
            /** @var ConstraintViolation $violation */
            $strs[] = sprintf(
                '%s: %s',
                $violation->getPropertyPath(),
                $violation->getMessage());
        }

        parent::__construct("Validation exception: ".join(', ', $strs));
    }

    /**
     * @return mixed
     */
    public function getViolations()
    {
        return $this->violations;
    }
}
