<?php

namespace Silo\Inventory\Repository;

use Doctrine\ORM\EntityRepository;
use Silo\Inventory\Collection\BatchCollection;

class ProductRepository extends EntityRepository
{
    private $cacheFindOneBySku = [];

    /**
     * Like findOneBySku, but stores the result in an array cache for the duration
     * of the thread.
     * @param string $sku
     */
    public function cachedFindOneBySku($sku)
    {
        if (!isset($this->cacheFindOneBySku[$sku])) {
            $this->cacheFindOneBySku[$sku] = $this->findOneBySku($sku);
        }

        return $this->cacheFindOneBySku[$sku];
    }

    public function batchFromMap ($map)
    {
        $batches = new BatchCollection();
        if (is_array($map)) {
            foreach ($map as $sku => $qty) {
                $product = $this->cachedFindOneBySku($sku);
                if (!$product) {
                    throw new Exception("No such Product:$sku");
                }
                $batches->addProduct($product, $qty);
            }
        }

        return $batches;
    }
}
