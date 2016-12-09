<?php

namespace Silo\Inventory\Model;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * An immutable (we hope so) quantity of Product.
 *
 * @ORM\Entity
 * @ORM\Table(name="batch")
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
     * @param Product $product What
     * @param int $quantity How much of it
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
     * @todo whacky, please refactor this
     * @param $quantity
     * @return int
     */
    public function addQuantity($quantity)
    {
        $this->quantity += $quantity;

        return $this->quantity;
    }

    /**
     * @param mixed $operation
     */
    public function setOperation($operation)
    {
        $this->operation = $operation;
    }

    /**
     * @param mixed $location
     */
    public function setLocation($location)
    {
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
    public function copyOpposite()
    {
        return new self($this->product, -$this->quantity);
    }

    /**
     * {@inheritdoc}
     */
    public function __toString()
    {
        return 'Batch:'.$this->operation.$this->location;
    }

    /**
     * Compare two Batches together by content
     *
     * @param self|null $a
     * @param self|null $b
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
