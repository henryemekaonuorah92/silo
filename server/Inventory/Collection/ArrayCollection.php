<?php

namespace Silo\Inventory\Collection;

use Doctrine\Common\Collections\Collection;

/**
 * Advanced operations on Batches ArrayCollection.
 */
class ArrayCollection extends \Doctrine\Common\Collections\ArrayCollection
{
    /**
     * @param Collection $collection
     * @return static
     */
    public static function fromCollection(Collection $collection)
    {
        return new static($collection->toArray());
    }

    /**
     * @param Collection $batches
     * @return $this
     */
    public function merge(Collection $batches)
    {
        $that = $this;
        $ref = $batches->toArray();
        array_walk($ref, function ($add) use ($that) {
            $this->add($add);
        });

        return $this;
    }

    public function addUnique($element)
    {
        if (!$this->contains($element)) {
            return $this->add($element);
        }

        return false;
    }

    public function mergeUnique(Collection $batches)
    {
        $that = $this;
        $ref = $batches->toArray();
        array_walk($ref, function ($add) use ($that) {
            $this->addUnique($add);
        });

        return $this;
    }
}
