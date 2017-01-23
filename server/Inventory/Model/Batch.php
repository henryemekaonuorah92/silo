<?php

namespace Silo\Inventory\Model;

use Doctrine\ORM\Mapping as ORM;

/**
 * An immutable (we hope so) quantity of Product.
 *
 * @ORM\Entity
 * @ORM\Table(name="batch",
 *     options={"comment":"Quantity of Product, immutable when Operation is set"},
 *     uniqueConstraints={
 *         @ORM\UniqueConstraint(name="product_operation_location_idx", columns={"product_id", "operation_id", "location_id"})
 *     }
 * )
 */
class Batch
{
    /**
     * @var int
     * @ORM\Id
     * @ORM\Column(name="batch_id", type="integer")
     * @ORM\GeneratedValue
     */
    private $id;

    /**
     * @var int
     * @ORM\Column(name="quantity", type="integer", options={"comment":"How much of a Product"})
     */
    private $quantity;

    /**
     * @var Product
     * @ORM\ManyToOne(targetEntity="Product")
     * @ORM\JoinColumn(name="product_id", referencedColumnName="product_id")
     */
    private $product;

    /**
     * @var Operation
     * @ORM\ManyToOne(targetEntity="Operation", inversedBy="productQuantities")
     * @ORM\JoinColumn(name="operation_id", referencedColumnName="operation_id")
     */
    private $operation;

    /**
     * @var Location
     * @ORM\ManyToOne(targetEntity="Location", inversedBy="productQuantities")
     * @ORM\JoinColumn(name="location_id", referencedColumnName="location_id")
     */
    private $location;

    /**
     * @param Product $product  What
     * @param int     $quantity How much of it
     *
     * @todo Evaluate float for quantity, dealing with other units
     */
    public function __construct(Product $product, $quantity)
    {
        $this->product = $product;
        $this->quantity = $quantity;
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

    /**
     * @param int $quantity Positive or negative quantity of product to add
     *
     * @return int
     */
    public function add($quantity)
    {
        $this->quantity += $quantity;

        return $this->quantity;
    }

    /**
     * @param mixed $operation
     */
    public function setOperation(Operation $operation)
    {
        if ($this->location) {
            throw new \LogicException('You cannot assign an Operation to a Batch with a Location');
        }
        if ($this->operation && $this->operation != $operation) {
            throw new \LogicException('You cannot change the Operation of a Batch');
        }
        $this->operation = $operation;
    }

    /**
     * @param mixed $location
     */
    public function setLocation(Location $location)
    {
        if ($this->operation) {
            throw new \LogicException('You cannot assign a Location to a Batch with an Operation');
        }
        if ($this->location && $this->location != $location) {
            throw new \LogicException('You cannot change the Location of a Batch');
        }
        $this->location = $location;
    }

    /**
     * @return Batch Value copy of $this
     */
    public function copy()
    {
        return new self($this->product, $this->quantity);
    }

    /**
     * @return Batch Value copy of $this with opposite quantity
     */
    public function opposite()
    {
        return new self($this->product, -$this->quantity);
    }

    /**
     * {@inheritdoc}
     */
    public function __toString()
    {
        return 'Batch:'.$this->operation.$this->location.':'.$this->product->getSku().'x'.$this->quantity;
    }

    /**
     * Compare two Batches together by content.
     *
     * @param self|null $a
     * @param self|null $b
     *
     * @return bool True if $a and $b are for the same Product and of the same quantity
     */
    public static function compare($a, $b)
    {
        if (!is_null($a) && !$a instanceof self) {
            throw new \InvalidArgumentException('$a should be a Batch instance or null');
        }
        if (!is_null($b) && !$b instanceof self) {
            throw new \InvalidArgumentException('$b should be a Batch instance or null');
        }
        if (is_null($a) && is_null($b)) {
            return true;
        }
        if (is_null($a) || is_null($b)) {
            return false;
        }

        return $a->product == $b->product && $a->quantity == $b->quantity;
    }
}
