<?php

namespace Silo\Inventory\Finder;

use Doctrine\ORM\QueryBuilder;
use Silo\Inventory\Model\Location;

class LocationFinder extends \Silo\Inventory\Finder\AbstractFinder
{
    private $isDeleted = false;

    protected function buildRoot(QueryBuilder $query)
    {
        return $query->select('l')->from('Inventory:Location', 'l');
    }

    public function onlyDeleted()
    {
        $this->isDeleted = true;

        return $this;
    }

    public function withModifier($modifierName)
    {
        $this->getQuery()
            ->join('l.modifiers', 'm')
            ->join('m.type', 't')
            ->andWhere('t.name = :type')
            ->setParameter('type', $modifierName)
        ;
    }

    /**
     * @return Location[]
     */
    public function find()
    {
        $this->getQuery()
            ->andWhere('l.isDeleted = :deleted')
            ->setParameter('deleted', $this->isDeleted)
        ;

        return $this->getQuery()->getQuery()->getResult();
    }

    /**
     * @return \Doctrine\ORM\Internal\Hydration\IterableResult
     */
    public function iterate()
    {
        $this->getQuery()
            ->orderBy('l.id', 'DESC')
            ->andWhere('l.isDeleted = :deleted')
            ->setParameter('deleted', $this->isDeleted)
        ;

        return $this->getQuery()->getQuery()->iterate();
    }
}
