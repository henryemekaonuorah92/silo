<?php

namespace Silo\Inventory\Finder;

use Silo\Inventory\Model\Context;
use Silo\Inventory\Model\User;

class OperationSetFinder extends \Silo\Inventory\Finder\AbstractFinder
{
    public function isPending()
    {
        $this->getQuery()
            ->andWhere($this->getQuery()->expr()->isNull('o.doneAt'))
            ->andWhere($this->getQuery()->expr()->isNull('o.cancelledAt'))
        ;

        return $this;
    }

    public function isType($type)
    {
        $this->getQuery()
            ->andWhere('type.name = :type')
            ->setParameter('type', $type)
        ;

        return $this;
    }

    public function ownedBy(User $user)
    {
        $this->getQuery()
            ->andWhere('c.user = :user')
            ->setParameter('user', $user)
        ;

        return $this;
    }

    /**
     * Return a Context with Operations
     * @return Context|null
     */
    public function findOne()
    {
        $this->getQuery()
            ->select('c')
            ->from('Inventory:OperationSet', 'c')
            ->innerJoin('c.operations', 'o')
            ->innerJoin('o.operationType', 'type')
            ->orderBy('c.id', 'DESC')
        ;

        return $this->getQuery()->getQuery()->getOneOrNullResult();
    }

    public function find()
    {
        $this->getQuery()
            ->select('c')
            ->from('Inventory:OperationSet', 'c')
            ->innerJoin('c.operations', 'o')
            ->innerJoin('o.operationType', 'type')
            ->orderBy('c.id', 'DESC')
        ;

        return $this->getQuery()->getQuery()->getResult();
    }

    /**
     * {@inheritdoc}
     */
    public function iterate()
    {
        $this->getQuery()
            ->select('c')
            ->from('Inventory:OperationSet', 'c')
            ->innerJoin('c.operations', 'o')
            ->innerJoin('o.operationType', 'type')
            ->orderBy('c.id', 'DESC')
        ;

        return $this->getQuery()->getQuery()->iterate();
    }
}
