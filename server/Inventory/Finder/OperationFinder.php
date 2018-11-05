<?php

namespace Silo\Inventory\Finder;

use Silo\Inventory\Collection\ArrayCollection;
use Silo\Inventory\Model\Location;
use Silo\Inventory\Model\Operation;
use Silo\Inventory\Model\OperationType;
use Silo\Inventory\Model\Product;
use Doctrine\ORM\Query;
use Silo\Inventory\Model\User;

class OperationFinder extends \Silo\Inventory\Finder\AbstractFinder
{
    /**
     * @param Location $location
     * @return $this
     */
    public function toLocation(Location $location)
    {
        $this->getQuery()
            ->andWhere('o.target = :to')
            ->setParameter('to', $location)
        ;

        return $this;
    }

    /**
     * @param Location $location
     * @return $this
     */
    public function toLocations(ArrayCollection $locations)
    {
        $this->getQuery()
            ->andWhere($this->getQuery()->expr()->in('o.target', ':locations'))
            ->setParameter('locations', $locations)
        ;

        return $this;
    }

    public function fromLocation(Location $location)
    {
        $this->getQuery()
            ->andWhere('o.source = :from')
            ->setParameter('from', $location)
        ;

        return $this;
    }

    /**
     * @param Location $location
     * @return $this
     */
    public function moving(Location $location)
    {
        $this->getQuery()
            ->andWhere('o.location = :moving')
            ->setParameter('moving', $location)
        ;

        return $this;
    }

    public function manipulating(Location $location)
    {
        $this->getQuery()
            ->andWhere($this->getQuery()->expr()->orX(
                'o.location = :manipulating',
                'o.target = :manipulating',
                'o.source = :manipulating'

            ))
            ->setParameter('manipulating', $location)
        ;

        return $this;
    }

    /**
     * @return $this
     */
    public function isPending()
    {
        $this->getQuery()
            ->andWhere($this->getQuery()->expr()->isNull('o.doneAt'))
            ->andWhere($this->getQuery()->expr()->isNull('o.cancelledAt'))
        ;

        return $this;
    }

    /**
     * @return $this
     */
    public function isDone()
    {
        $this->getQuery()
            ->andWhere($this->getQuery()->expr()->isNotNull('o.doneAt'))
        ;

        return $this;
    }

    public function isOlderThan(\DateTime $time)
    {
        $this->getQuery()
            ->andWhere($this->getQuery()->expr()->lte('o.requestedAt', ':olderThan'))
            ->setParameter('olderThan', $time->format('Y-m-d H:i:s'))
        ;

        return $this;
    }

    public function isNewerThan(\DateTime $time)
    {
        $this->getQuery()
            ->andWhere($this->getQuery()->expr()->gte('o.requestedAt', ':newerThan'))
            ->setParameter('newerThan', $time->format('Y-m-d H:i:s'))
        ;

        return $this;
    }

    public function requestedBetween(\DateTime $start, \DateTime $end)
    {
        $this->getQuery()
            ->andWhere($this->getQuery()->expr()->between('o.requestedAt', ':start', ':end'))
            ->setParameter('start', $start->format('Y-m-d H:i:s'))
            ->setParameter('end', $end->format('Y-m-d H:i:s'))
        ;

        return $this;
    }

    /**
     * @param $type
     * @return $this
     */
    public function isType($type)
    {
        if(!is_array($type)) {
            $type = [$type];
        }
        $this->getQuery()
            ->andWhere('type.name in (:type)')
            ->setParameter('type', $type)
        ;

        return $this;
    }

    public function withoutContext()
    {
        $this->getQuery()
            ->andWhere($this->getQuery()->expr()->isNull('operationSet.id'));

        return $this;
    }

    public function countItemsOf(Product $product)
    {
        $this->getQuery()
            ->select('SUM(batch.quantity)')
            ->from('Inventory:Operation', 'o')
            ->innerJoin('o.batches', 'batch')
            ->join('o.operationType', 'type')
            ->andWhere('batch.product = :product')
            ->setParameter('product', $product)
        ;

        return $this->getQuery()->getQuery()->getResult(Query::HYDRATE_SINGLE_SCALAR);
    }

    public function count()
    {
        $this->getQuery()
            ->select('COUNT(o)')
            ->from('Inventory:Operation', 'o')
            ->join('o.operationType', 'type')
            ->leftJoin('o.operationSets', 'operationSet')
            ;

        if ($this->loadBatches) {
            $this->getQuery()
                ->innerJoin('o.batches', 'batches');
        }

        return $this->getQuery()->getQuery()->getResult(Query::HYDRATE_SINGLE_SCALAR);
    }

    /**
     * @return Operation[]
     */
    public function find()
    {
        $this->getQuery()
            ->select('o')
            ->from('Inventory:Operation', 'o')
            ->leftJoin('o.operationType', 'type')
            ->leftJoin('o.operationSets', 'operationSet')
            ->orderBy('o.requestedAt', 'DESC');

        if ($this->loadBatches) {
            $this->getQuery()
                ->addSelect('batches')
                ->innerJoin('o.batches', 'batches');
        }

        if ($this->loadLocations) {
            $this->getQuery()
                ->addSelect('target, source, location')
                ->innerJoin('o.target', 'target')
                ->innerJoin('o.source', 'source')
                ->innerJoin('o.location', 'location');
        }

        return $this->getQuery()->getQuery()->getResult();
    }

    /**
     * @return Operation[]
     */
    public function findOneOrNull()
    {
        $this->getQuery()
            ->select('o')
            ->from('Inventory:Operation', 'o')
            ->leftJoin('o.operationType', 'type')
            ->leftJoin('o.operationSets', 'operationSet')
            ->orderBy('o.requestedAt', 'DESC');

        return $this->getQuery()->getQuery()->getOneOrNullResult();
    }

    public function withIds(array $ids)
    {
        $this->getQuery()
            ->andWhere($this->getQuery()->expr()->in('o.id', ':ids'))
            ->setParameter('ids', $ids);

        return $this;
    }

    private $loadBatches = false;

    private $loadLocations = false;

    public function withBatches()
    {
        $this->loadBatches = true;

        return $this;
    }

    public function withLocations()
    {
        $this->loadLocations = true;

        return $this;
    }

    /**
     * @return \Doctrine\ORM\Internal\Hydration\IterableResult
     */
    public function iterate()
    {
        $this->getQuery()
            ->select('o')
            ->orderBy('o.id', 'DESC')
            ->from('Inventory:Operation', 'o');

        return $this->getQuery()->getQuery()->iterate();
    }
}
