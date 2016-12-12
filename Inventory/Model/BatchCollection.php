<?php

namespace Silo\Inventory\Model;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

/**
 * Advanced operations on Batches ArrayCollection
 */
class BatchCollection extends ArrayCollection
{
    /**
     * Create a new BatchCollection out of a Collection
     * @param Collection $collection
     * @return static
     */
    public static function fromCollection(Collection $collection)
    {
        return new static($collection->toArray());
    }

    /**
     * Return a BatchCollection with a copy of each Batch in $this
     * @return static
     */
    public function copy()
    {
        return new static(array_map(function (Batch $batch) {return $batch->copy();}, $this->toArray()));
    }

    /**
     * Return a BatchCollection with a opposite copy of each Batch in $this
     * @return static
     */
    public function opposite()
    {
        return new static(array_map(function (Batch $batch) {return $batch->opposite();}, $this->toArray()));
    }

    /**
     * {@inheritdoc}
     *
     * Specific to BatchCollection, contains a Batch with the same content
     */
    public function contains($element)
    {
        if (!$element instanceof Batch) {
            throw new \InvalidArgumentException('$element should be of type Batch');
        }
        foreach ($this->toArray() as $batch) {
            if (Batch::compare($batch, $element)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Merge a Batches collection into this one, keeping only one single Batch per different Product.
     *
     * @param Collection $batches
     * @param bool $add
     * @return $this
     */
    public function merge(Collection $batches)
    {
        $that = $this;
        $ref = $batches->toArray();
        array_walk($ref, function (Batch $increment) use ($that) {
            // If there's already a Product matching, we increment it,
            // or we add a new Batch entry
            $found = $this->filter(function (Batch $batch) use ($increment) {
                return $increment->getProduct()->getSku() == $batch->getProduct()->getSku();
            });
            if ($found->count() == 1) {
                $found[0]->add($increment->getQuantity());
            } else if ($found->count() > 1) {
                throw new \LogicException('You cannot have many Batch with the same Product');
            } else {
                $that->add($increment);
            }
        });

        return $this;
    }
}
