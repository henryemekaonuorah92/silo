<?php

namespace Silo\Inventory\Finder;

use Doctrine\ORM\Query\Parameter;
use Doctrine\ORM\QueryBuilder;
use Silo\Inventory\Collection\ArrayCollection;
use Silo\Inventory\Model\Location;

class LocationFinder extends \Silo\Inventory\Finder\AbstractFinder
{
    private $isDeleted = false;

    protected function buildRoot(QueryBuilder $query)
    {
        return $query->select('l'.$this->suffix)->from('Inventory:Location', 'l'.$this->suffix);
    }

    public function onlyDeleted()
    {
        $this->isDeleted = true;

        return $this;
    }

    private $joinModifier = false;

    protected function joinModifier()
    {
        if (!$this->joinModifier) {
            $this->getQuery()
                ->join('l'.$this->suffix.'.modifiers', 'm'.$this->suffix)
                ->join('m'.$this->suffix.'.type', 't'.$this->suffix);
            $this->joinModifier = true;
        }

        return $this;
    }

    public function withModifiers()
    {
        return $this->joinModifier();
    }

    public function excluding(self $exclude)
    {
        $q = $this->getQuery();
        $q->andWhere($q->expr()->notIn('l'.$this->suffix, $exclude->getQuery()->getDQL()));
        $exclude->getQuery()->getParameters()->map(function (Parameter $parameter) use ($q) {
            $q->setParameter($parameter->getName(), $parameter->getValue());
        });

        return $this;
    }

    /**
     * @param $modifierName
     * @return $this
     */
    public function withModifier($modifierName)
    {
        $this->joinModifier()->getQuery()
            ->andWhere('t'.$this->suffix.'.name = :type'.$this->suffix)
            ->setParameter('type'.$this->suffix, $modifierName)
        ;

        return $this;
    }

    /**
     * @return Location[]
     */
    public function find()
    {
        $this->getQuery()
            ->andWhere('l'.$this->suffix.'.isDeleted = :deleted'.$this->suffix)
            ->setParameter('deleted'.$this->suffix, $this->isDeleted)
        ;

        return $this->getQuery()->getQuery()->getResult();
    }

    public function hasCode($code)
    {
        $this->getQuery()
            ->andWhere('l'.$this->suffix.'.code = :code'.$this->suffix)
            ->setParameter('code'.$this->suffix, $code)
        ;

        return $this;
    }

    /**
     * @return Location|null
     */
    public function findOne()
    {
        $this->getQuery()
            ->andWhere('l'.$this->suffix.'.isDeleted = :deleted')
            ->setParameter('deleted', $this->isDeleted)
        ;

        return $this->getQuery()->getQuery()->getOneOrNullResult();
    }

    /**
     * @return \Doctrine\ORM\Internal\Hydration\IterableResult
     */
    public function iterate()
    {
        $this->getQuery()
            ->orderBy('l'.$this->suffix.'.id', 'DESC')
            ->andWhere('l'.$this->suffix.'.isDeleted = :deleted')
            ->setParameter('deleted', $this->isDeleted)
        ;

        return $this->getQuery()->getQuery()->iterate();
    }
}
