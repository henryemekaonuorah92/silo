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

    public function __construct(EntityManager $em, \Closure $queryExtender = null)
    {
        $this->em = $em;
        $this->queryExtender = $queryExtender;
    }

    /**
     * Apply a mapReduce algorithm on a subtree starting at $location, extracting $map
     * on each node and returning the value given by $reduce.
     *
     * $reduce is passed for each node a new value and the reminder
     *
     * @param Location $location
     * @param callable $map
     * @param callable $reduce
     * @return mixed
     * @todo Warn for cycles !
     */
    public function mapReduce(Location $location, callable $map, callable $reduce, $reduceInit)
    {
        $reminder = call_user_func($map, $location);

        $query = $this->em->createQueryBuilder();
        $query->addSelect('Location')
            ->from('Inventory:Location', 'Location')
            ->andWhere('Location.parent = :parent')
            ->setParameter('parent', $location);

        if (is_callable($this->queryExtender)) {
            call_user_func($this->queryExtender, $query);
        }

        $childs = $query->getQuery()->getResult();

        foreach ($childs as $child) {
            $reminder = $this->mapReduce($child, $map, $reduce, $reminder);
            $this->em->detach($child);
        }

        return call_user_func($reduce, $reminder, $reduceInit);
    }
}
