<?php

namespace Silo\Inventory\Collection;

use Silo\Inventory\Model\Batch;
use Silo\Inventory\Model\Operation;

/**
 * Advanced operations on Operations ArrayCollection.
 */
class OperationCollection extends ArrayCollection
{
    public function getTypes()
    {
        $typeMap = [];
        foreach ($this as $operation) { /** @var Operation $operation */
            $t = $operation->getType();
            $typeMap[$t] = isset($typeMap[$t]) ? $typeMap[$t] + 1 : 0;
        }

        return array_keys($typeMap);
    }

    /**
     * @return ArrayCollection
     */
    public function getTargets()
    {
        $targets = new ArrayCollection();
        foreach ($this as $operation) { /** @var Operation $operation */
            $targets->addUnique($operation->getTarget());
        }

        return $targets;
    }

    /**
     * @return BatchCollection All batches contained by $this Operations
     */
    public function getBatches()
    {
        $batches = new BatchCollection();

        foreach ($this->toArray() as $operation) {
            $batches->merge($operation->getBatches());
        }

        return $batches;
    }

    /**
     * @param $type
     * @return static
     */
    public function filterType($type)
    {
        return $this->filter(function (Operation $operation) use ($type) {
            return $operation->getType() === $type;
        });
    }

    /**
     * @return static
     */
    public function filterDone()
    {
        return $this->filter(function (Operation $operation) {
            return $operation->getStatus()->isDone();
        });
    }
}
