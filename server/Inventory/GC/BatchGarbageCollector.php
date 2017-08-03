<?php

namespace Silo\Inventory\GC;

use Psr\Log\LoggerAwareTrait;
use Silo\Base\EntityManagerAwareTrait;

class BatchGarbageCollector
{
    use EntityManagerAwareTrait;
    use LoggerAwareTrait;

    // @todo add some probe reporting here
    public function collect()
    {
        $q = $this->em->createQuery(<<<EOQ
        DELETE FROM Inventory:Batch b
        WHERE b.quantity = 0
EOQ
        );

        $deletedCount = $q->execute();
        if ($deletedCount > 0) {
            $this->logger->info(sprintf("%s garbage collected %s", self::class, $deletedCount));
        } else {
            $this->logger->debug(sprintf("%s garbage collected nothing", self::class));
        }
    }
}
