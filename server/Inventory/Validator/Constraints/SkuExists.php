<?php

namespace Silo\Inventory\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * Make sure all shipping order items have stock.
 */
class SkuExists extends Constraint
{
    public $message = '%sku% does not exist';

    /** {@inheritdoc} */
    public function getTargets()
    {
        return self::CLASS_CONSTRAINT;
    }
}
