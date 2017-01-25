<?php

namespace Silo\Inventory\Repository;

use Doctrine\ORM\EntityRepository;

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
}
