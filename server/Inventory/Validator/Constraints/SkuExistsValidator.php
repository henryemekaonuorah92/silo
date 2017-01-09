<?php

namespace Silo\Inventory\Validator\Constraints;

use Silo\Base\ContainerAwareInterface;
use Silo\Base\ContainerAwareTrait;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class SkuExistsValidator extends ConstraintValidator implements ContainerAwareInterface
{
    // @todo injecting the container is a bit too wild, swap for entitymanager or repository
    use ContainerAwareTrait;

    public function validate($value, Constraint $constraint)
    {
        $products = $this->container['em']->getRepository('Inventory:Product');
        $product = $products->findOneBy(['sku' => $value]);

        if (!$product) {
            $this->context->buildViolation($constraint->message)
                ->setParameter('%sku%', $value)
                ->addViolation();
        }
    }
}
