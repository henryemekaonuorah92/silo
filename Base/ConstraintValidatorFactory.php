<?php

namespace Silo\Base;

use Pimple\Container;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\ExpressionValidator;
use Symfony\Component\Validator\ConstraintValidatorFactoryInterface;

class ConstraintValidatorFactory implements ConstraintValidatorFactoryInterface
{
    /** @var Container */
    private $container;

    protected $validators = array();

    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    /**
     * {@inheritdoc}
     */
    public function getInstance(Constraint $constraint)
    {
        $className = $constraint->validatedBy();

        if (!isset($this->validators[$className])) {
            if ('validator.expression' === $className) {
                $this->validators[$className] = new ExpressionValidator();
            } else {
                $this->validators[$className] = new $className();
            }

            if ($this->validators[$className] instanceof ContainerAwareInterface) {
                $this->validators[$className]->setContainer($this->container);
            }
        }

        return $this->validators[$className];
    }
}
