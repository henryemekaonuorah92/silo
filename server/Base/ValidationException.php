<?php

namespace Silo\Base;

use Symfony\Component\Validator\ConstraintViolationListInterface;

class ValidationException extends \Exception
{
    protected $violations;

    public function __construct(ConstraintViolationListInterface $violations)
    {
        $this->violations = $violations;
        parent::__construct("Validation exception");
    }

    /**
     * @return mixed
     */
    public function getViolations()
    {
        return $this->violations;
    }
}