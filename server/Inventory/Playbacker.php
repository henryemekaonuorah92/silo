<?php

namespace Silo\Inventory;

use Silo\Base\EntityManagerAwareTrait;
use Silo\Inventory\Finder\OperationFinder;
use Silo\Inventory\Model\Location;
use Silo\Inventory\Model\Operation;

class Playbacker
{
    use EntityManagerAwareTrait;

    /**
     * Give back the stock at this moment in time
     *
     * @param Location $location
     * @param \DateTime $time
     */
    public function getBatchesAt(Location $location, \DateTime $time)
    {
        $now = $location->getBatches();
        $finder = OperationFinder::create($this->em);
        $operations = $finder->manipulating($location)
            ->isNewerThan($time)
            ->withBatches()
            ->find();

        // We replay in time all operations
        foreach ($operations as $operation) {
            if (Location::compare($operation->getLocation(), $location)) {
                continue;
            }

            /** @var Operation $operation */
            $batches = $operation->getBatches();
            if (Location::compare($operation->getSource(), $location)) {
                $now->merge($batches);
            } elseif (Location::compare($operation->getTarget(), $location)) {
                $now->diff($batches);
            }
        }

        return $now;
    }
}
