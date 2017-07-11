<?php

namespace Silo\Inventory;

use Doctrine\ORM\EntityManager;
use Silo\Inventory\Model\Location;

/**
 * Walk a Location hierarchy.
 */
class LocationWalker
{
    /** @var EntityManager */
    private $em;

    private $queryExtender;

    private $exceptionOnLoop;

    public function __construct(EntityManager $em, \Closure $queryExtender = null, $exceptionOnLoop = false)
    {
        $this->em = $em;
        $this->queryExtender = $queryExtender;
        $this->exceptionOnLoop = $exceptionOnLoop;
    }

    private $loopDetect = [];

    /**
     * Apply a mapReduce algorithm on a subtree starting at $location, extracting $map
     * on each node and returning the value given by $reduce.
     *
     * $reduce is passed for each node a new value and the reminder
     *
     * @param Location $location
     * @param callable $mapFn
     * @param callable $reduceFn
     * @return mixed
     * @todo Warn for cycles !
     */
    public function mapReduce(Location $location, callable $mapFn, callable $reduceFn, $reduceInitial)
    {
        // Loop avoidance by maintaining a explored node list
        $code = $location->getCode();
        if (in_array($code, $this->loopDetect)) {
            if ($this->exceptionOnLoop) {
                throw new \Exception("Loop detected");
            }
            return $reduceInitial;
        }
        array_push($this->loopDetect, $code);

        // First fetch all childs and apply mapReduce recursively on them
        $query = $this->em->createQueryBuilder();
        $query->addSelect('Location')
            ->from('Inventory:Location', 'Location')
            ->andWhere('Location.parent = :parent')
            ->setParameter('parent', $location);
        if (is_callable($this->queryExtender)) {
            call_user_func($this->queryExtender, $query);
        }
        $childs = $query->getQuery()->getResult();

        $mapped = [];
        foreach ($childs as $child) {
            if (is_object($reduceInitial)) {
                $reduceInitial = clone $reduceInitial;
            }
            array_push($mapped, $this->mapReduce($child, $mapFn, $reduceFn, $reduceInitial));
            $this->em->detach($child);
        }

        // Apply mapping function on current !
        array_push($mapped, $mapFn($location));

        if (is_object($reduceInitial)) {
            $reduceInitial = clone $reduceInitial;
        }
        return array_reduce($mapped, $reduceFn, $reduceInitial);
    }
}
