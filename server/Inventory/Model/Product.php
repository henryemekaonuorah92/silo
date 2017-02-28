<?php

namespace Silo\Inventory\Model;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="Silo\Inventory\Repository\ProductRepository")
 * @ORM\Table(name="product", uniqueConstraints={
 *     @ORM\UniqueConstraint(name="product_idx", columns={"sku"})
 * })
 */
class Product
{
    /**
     * @var int
     *
     * @ORM\Id
     * @ORM\Column(name="product_id", type="integer")
     * @ORM\GeneratedValue
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="sku", type="string", length=255)
     */
    private $sku = '';

    public function __construct($sku)
    {
        $this->sku = $sku;
    }

    /**
     * @return string
     */
    public function getSku()
    {
        return $this->sku;
    }

    public function __toString()
    {
        return $this->sku;
    }
}
