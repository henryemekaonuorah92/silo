<?php

namespace Silo\Inventory\Model;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Silo\Base\Model\Collectable;

/**
 * An immutable quantity of Product
 *
 * @ORM\Entity
 * @ORM\Table(name="g_batch")
 */
class Batch
{
    /**
     * @var integer
     * @ORM\Id
     * @ORM\Column(name="batch_id", type="integer")
     * @ORM\GeneratedValue
     */
    private $id;

    /**
     * @var integer
     * @ORM\Column(name="quantity", type="integer")
     */
    private $quantity;

    /**
     * @var Product
     * @ORM\ManyToOne(targetEntity="Product")
     * @ORM\JoinColumn(name="product_id", referencedColumnName="product_id")
     */
    private $product;

    /**
     * Many Batches have One Operation.
     * @ORM\ManyToOne(targetEntity="Operation", inversedBy="productQuantities")
     * @ORM\JoinColumn(name="operation_id", referencedColumnName="operation_id")
     */
    private $operation;

    /**
     * Many Batches have One Location.
     * @ORM\ManyToOne(targetEntity="Location", inversedBy="productQuantities")
     * @ORM\JoinColumn(name="location_id", referencedColumnName="location_id")
     */
    private $location;

    public function __construct(Product $product, $quantity)
    {
        $this->product = $product;
        $this->quantity = $quantity;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return int
     */
    public function getQuantity()
    {
        return $this->quantity;
    }

    /**
     * @return Product
     */
    public function getProduct()
    {
        return $this->product;
    }

    public function addQuantity($quantity)
    {
        $this->quantity += $quantity;

        return $this->quantity;
    }

    /**
     * @param \Doctrine\Common\Collections\Collection $productQuantities
     * @return True if $this is inside the $productQuantities collection
     */
    public function isMemberOf(\Doctrine\Common\Collections\Collection $productQuantities)
    {
        $found = false;
        foreach($productQuantities as $productQuantity) {
            if (
                $productQuantity->getQuantity() == $this->getQuantity() &&
                $productQuantity->getProduct()->getId() == $this->getProduct()->getId()
            ) {
                $found = true;
            }
        }

        return $found;
    }
}
