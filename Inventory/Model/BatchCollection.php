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
     * @todo whacky, please refactor
     * @param Collection $batches
     * @return BatchCollection
     */
    public function incrementBy(Collection $batches)
    {
        return $this->changeBy($batches);
    }

    /**
     * @todo whacky, please refactor
     * @param Collection $batches
     * @return BatchCollection
     */
    public function decrementBy(Collection $batches)
    {
        return $this->changeBy($batches, false);
    }

    /**
     * Merge a Batches collection into this one, keeping only one single Batch per different Product.
     *
     * @todo I suspect this thing from being buggy and creating duplicate Batches
     * @param Collection $batches
     * @param bool $add
     * @return $this
     */
    private function changeBy(Collection $batches, $add = true)
    {
        $that = $this;
        $batches->forAll(function ($key, Batch $increment) use ($that, $add) {
            // If there's already a Product matching, we increment it,
            // or we add a new Batch entry
            $found = $this->filter(function (Batch $batch) use ($increment) {
                return $increment->getProduct()->getSku() == $batch->getProduct()->getSku();
            });
            if ($found->count() == 1) {
                $found[0]->addQuantity($add ? $increment->getQuantity() : $increment->copyOpposite()->getQuantity());
            } if ($found->count() > 1) {
                throw new \Exception('shit');
            } else {
                $add ? $that->add($increment) : $that->add($increment->copyOpposite());
            }
        });

        return $this;
    }
}
