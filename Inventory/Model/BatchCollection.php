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
        array_walk($ref, function (Batch $add) use ($that) {
            $this->addProduct($add->getProduct(), $add->getQuantity());
        });

        return $this;
    }

    /**
     * Add a single quantity of product to the current BatchCollection
     *
     * @param Product $product
     * @param $quantity
     */
    public function addProduct(Product $product, $quantity)
    {
        $found = $this->filter(function (Batch $batch)use($product) {
            return $product->getSku() == $batch->getProduct()->getSku();
        });
        if ($found->count() == 1) {
            $foundValues = $found->getValues();
            $foundValues[0]->add($quantity);
        } else if ($found->count() > 1) {
            throw new \LogicException('You cannot have many Batch with the same Product');
        } else {
            $this->add(new Batch($product, $quantity));
        }
    }
}
