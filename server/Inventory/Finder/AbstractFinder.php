<?php

namespace Silo\Inventory\Finder;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\QueryBuilder;

abstract class AbstractFinder
{
    /** @var QueryBuilder */
    private $query;

    public function __clone()
    {
        $this->query = clone $this->query;
    }

    /**
     * @param EntityManager $em
     */
    public function __construct(EntityManager $em)
    {
        $this->query = $this->buildRoot($em->createQueryBuilder());

    }

    public static function create(EntityManager $em)
    {
        return new static($em);
    }

    /**
     * @param QueryBuilder $query
     * @return QueryBuilder
     */
    protected function buildRoot(QueryBuilder $query)
    {
        return $query;
    }

    /**
     * @return QueryBuilder
     */
    public function getQuery()
    {
        return $this->query;
    }

    /**
     * Actually execute the finder.
     */
    abstract public function find();

    /**
     * @return \Doctrine\ORM\Internal\Hydration\IterableResult
     */
    abstract public function iterate();

    public function limit($size = null)
    {
        if (!is_null($size)) {
            $this->getQuery()->setMaxResults($size);
        }

        return $this;
    }
}
