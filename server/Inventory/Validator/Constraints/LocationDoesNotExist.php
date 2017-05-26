<?php

namespace Silo\Inventory\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * Make sure all shipping order items have stock.
 */
class LocationDoesNotExist extends Constraint
{
    public $message = '%code% does already exist';

    /** {@inheritdoc} */
    public function getTargets()
    {
        return self::CLASS_CONSTRAINT;
    }
}
