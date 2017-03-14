<?php

namespace Silo\Inventory\Collection;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Silo\Inventory\Collection\BatchCollection;
use Silo\Inventory\Model\Batch;
use Silo\Inventory\Model\Operation;

/**
 * Advanced operations on Operations ArrayCollection.
 */
class OperationCollection extends ArrayCollection
{
    /**
     * Create a new BatchCollection out of a Collection.
     *
     * @param Collection $collection
     *
     * @return static
     */
    public static function fromCollection(Collection $collection)
    {
        return new static($collection->toArray());
    }

    /**
     * Return a BatchCollection with a copy of each Batch in $this.
     *
     * @return static
     */
//    public function copy()
//    {
//        return new static(array_map(function (Batch $batch) {return $batch->copy();}, $this->toArray()));
//    }

    /**
     * {@inheritdoc}
     *
     * Specific to BatchCollection, contains a Batch with the same content
     */
    public function contains($element)
    {
        if (!$element instanceof Batch) {
            throw new \InvalidArgumentException('$element should be of type Batch');
        }
        foreach ($this->toArray() as $batch) {
            if (Batch::compare($batch, $element)) {
                return true;
            }
        }

        return false;
    }

    public function getTypes()
    {
        $typeMap = [];
        foreach ($this as $operation) { /** @var Operation $operation */
            $t = $operation->getType();
            $typeMap[$t] = isset($typeMap[$t]) ? $typeMap[$t] + 1 : 0;
        }

        return array_keys($typeMap);
    }

    public function getTargets()
    {
        $typeMap = [];
        foreach ($this as $operation) { /** @var Operation $operation */
            $t = $operation->getTarget()->getCode();
            $typeMap[$t] = isset($typeMap[$t]) ? $typeMap[$t] + 1 : 0;
        }

        return array_keys($typeMap);
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
}
