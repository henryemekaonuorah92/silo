<?php

namespace Silo\Inventory\Collection;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

/**
 * Advanced operations on Operations ArrayCollection.
 */
class LocationCollection extends ArrayCollection
{
    /**
     * Create a new BatchCollection out of a Collection.
     *
     * @param Collection $collection
     *
     * @return static
     */
    public static function fromCollection(Collection $collection)
    {
        return new static($collection->toArray());
    }


}
