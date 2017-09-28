<?php

namespace Silo\Inventory\GC;

use Silo\Base\EntityManagerAware;
use Silo\Base\EntityManagerAwareTrait;

class OperationGarbageCollector implements EntityManagerAware
{
    use EntityManagerAwareTrait;

    /**
     * {@inheritdoc}
     */
    public function collect(\DateTime $horizon)
    {
        // Delete related batches first
        $q = $this->em->createQuery(
            <<<EOQ
        DELETE Inventory:Batch b
        WHERE b.operation IN (
            SELECT o.id FROM Inventory:Operation o
            WHERE o.requestedAt <= :horizon
        )
EOQ
        );
        $q->setParameter('horizon', $horizon->format('Y-m-d H:i:s'));
        $removedBatches = $q->execute();

        // Delete related silo_operation_set_operations
        $sql = <<<EQO
        DELETE FROM silo_operation_set_operations
        WHERE operation_id IN (
            SELECT operation_id FROM silo_operation
            WHERE requested_at <= :horizon
        )
EQO;
        $stmt = $this->em->getConnection()->prepare($sql);
        $stmt->execute(array('horizon' => $horizon->format('Y-m-d H:i:s')));
        $removedSets = $stmt->rowCount();

        // Delete operations at last
        $q2 = $this->em->createQuery(
            <<<EOQ
        DELETE Inventory:Operation o
        WHERE o.requestedAt <= :horizon
EOQ
        );
        $q2->setParameter('horizon', $horizon->format('Y-m-d H:i:s'));

        $removedOperations = $q2->execute();

        return $removedBatches + $removedSets + $removedOperations;
    }
}
