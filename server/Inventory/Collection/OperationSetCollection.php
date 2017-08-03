<?php

namespace Silo\Inventory\Collection;

use Silo\Inventory\Model\Modifier;

/**
 * Advanced operations on OperationSets ArrayCollection.
 */
class OperationSetCollection extends ArrayCollection
{
    /**
     * @return OperationCollection
     */
    public function getOperations()
    {
        $ops = new OperationCollection();
        foreach ($this->toArray() as $set) { /** @var OperationSet $set */
            $ops->merge($set->getOperations());
        }

        return $ops;
    }
}
