<?php

namespace Silo\Inventory\Model;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

/**
 * Advanced operations on Batch ArrayCollection for models using them.
 */
class BatchCollection extends ArrayCollection
{
    public static function fromCollection(Collection $collection)
    {
        return new self($collection->toArray());
    }

    public function incrementBy(Collection $batches)
    {
        $that = $this;
        $batches->forAll(function($key, Batch $increment)use($that){
            // If there'S already a Product matching, we increment it,
            // or we add a new Batch entry
            $found = $this->filter(function(Batch $batch)use($increment){
                return $increment->getProduct()->getSku() == $batch->getProduct()->getSku();
            });
            if (count($found) == 1) {
                $found->addQuantity($increment->getQuantity());
            } if (count($found) > 1) {
                throw new \Exception('shit');
            } else {
                $that->add($increment);
            }
        });
    }

    public function decrementBy(Collection $batches)
    {
        $that = $this;
        $batches->forAll(function($key, Batch $increment)use($that){
            // If there'S already a Product matching, we increment it,
            // or we add a new Batch entry
            $found = $this->filter(function(Batch $batch)use($increment){
                return $increment->getProduct()->getSku() == $batch->getProduct()->getSku();
            });
            if ($found) {
                $found->addQuantity(-$increment->getQuantity());
            } else {
                throw new \Exception("fuck");
                $that->add($increment);
            }
        });
    }
}