<?php

namespace Silo\Inventory\GC;

use Silo\Base\EntityManagerAwareTrait;
use Silo\Base\EntityManagerAware;

class BatchGarbageCollector implements GarbageCollectorInterface, EntityManagerAware
{
    use EntityManagerAwareTrait;

    /**
     * {@inheritdoc}
     */
    public function collect(\DateTime $horizon)
    {
        $q = $this->em->createQuery(
            <<<EOQ
        DELETE Inventory:Batch b
        WHERE b.quantity = 0
EOQ
        );

        return $q->execute();
    }
}
