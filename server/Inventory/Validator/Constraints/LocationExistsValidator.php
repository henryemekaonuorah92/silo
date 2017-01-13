<?php

namespace Silo\Inventory\Validator\Constraints;

use Silo\Base\ContainerAwareInterface;
use Silo\Base\ContainerAwareTrait;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class LocationExistsValidator extends ConstraintValidator implements ContainerAwareInterface
{
    // @todo injecting the container is a bit too wild, swap for entitymanager or repository
    use ContainerAwareTrait;

    public function validate($value, Constraint $constraint)
    {
        if ($value == null || $value == 'VOID') {
            return;
        }

        $locations = $this->container['em']->getRepository('Inventory:Location');
        $location = $locations->findOneBy(['code' => $value]);

        if (!$location) {
            $this->context->buildViolation($constraint->message)
                ->setParameter('%code%', $value)
                ->addViolation();
        }
    }
}
