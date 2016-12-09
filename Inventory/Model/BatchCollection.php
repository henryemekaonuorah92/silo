<?php

namespace Silo\Inventory\Model;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Util\Debug;

/**
 * Advanced operations on Batch ArrayCollection for models using them.
 */
class BatchCollection extends ArrayCollection
{
    public static function fromCollection(Collection $collection)
    {
        return new static($collection->toArray());
    }

    public function copy()
    {
        return new static(array_map(function(Batch $batch){return $batch->copy();}, $this->toArray()));
    }

    /**
     * {@inheritDoc}
     */
    public function contains($element)
    {
        if (!$element instanceof Batch) {
            throw new \InvalidArgumentException('$element should be of type Batch');
        }
        foreach($this->toArray() as $batch) {
            if (Batch::compare($batch, $element)) {

                return true;
            }
        }

        return false;
    }

    public function incrementBy(Collection $batches)
    {
        return $this->changeBy($batches);
    }

    public function decrementBy(Collection $batches)
    {
        return $this->changeBy($batches, false);
    }

    private function changeBy(Collection $batches, $add = true)
    {
        $that = $this;
        $batches->forAll(function($key, Batch $increment)use($that, $add){
            // If there's already a Product matching, we increment it,
            // or we add a new Batch entry
            $found = $this->filter(function(Batch $batch)use($increment){
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