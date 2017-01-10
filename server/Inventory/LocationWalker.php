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

    public function __construct(EntityManager $em)
    {
        $this->em = $em;
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
     */
    public function mapReduce(Location $location, callable $map, callable $reduce, $reduceInit)
    {
        $reminder = call_user_func($map, $location);

        $childs = $this->em->getRepository('Inventory:Location')
            ->findBy(['parent' => $location]);
        foreach ($childs as $child) {
            $reminder = $this->mapReduce($child, $map, $reduce, $reminder);
        }

        return call_user_func($reduce, $reminder, $reduceInit);
    }
}
