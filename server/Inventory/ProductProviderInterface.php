<?php

namespace Silo\Inventory;

use Silo\Inventory\Model\Product;

interface ProductProviderInterface
{
    /**
     * @param string $sku
     * @return Product|null
     */
    public function getProduct($sku);
}
