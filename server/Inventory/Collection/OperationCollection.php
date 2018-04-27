<?php

namespace Silo\Inventory\Collection;

use Silo\Inventory\Model\MarshallableInterface;
use Silo\Inventory\Model\Batch;
use Silo\Inventory\Model\Operation;
use Silo\Inventory\Model\OperationSet;

/**
 * Advanced operations on Operations ArrayCollection.
 */
class OperationCollection extends ArrayCollection implements MarshallableInterface
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
     * @param $type
     * @return static
     */
    public function filterTypes(array $types)
    {
        return $this->filter(function (Operation $operation) use ($types) {
            return in_array($operation->getType(), $types);
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

    public function marshall()
    {
        return array_map(
            function (Operation $op) {
                return [
                    'id' => $op->getId(),
                    'source' => $op->getSource() ? $op->getSource()->getCode() : null,
                    'target' => $op->getTarget() ? $op->getTarget()->getCode() : null,
                    'type' => $op->getType(),
                    'status' => $op->getStatus()->toArray(),

                    'location' => $op->getLocation() ? $op->getLocation()->getCode() : null,
                    'contexts' => array_map(function (OperationSet $context) {
                        return [
                            'id' => $context->getId(),
                            'value' => $context->getValue()
                        ];
                    }, $op->getOperationSets()),
                    'batches' => $op->getBatches()->toRawArray()
                ];
            },
            $this->toArray()
        );
    }
}
