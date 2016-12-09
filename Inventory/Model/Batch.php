<?php

namespace Silo\Inventory\Model;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Silo\Base\Model\Collectable;

/**
 * An immutable quantity of Product
 *
 * @ORM\Entity
 * @ORM\Table(name="batch")
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

    public function copy()
    {
        return new self($this->product, $this->quantity);
    }

    public function copyOpposite()
    {
        return new self($this->product, -$this->quantity);
    }

    public function __toString()
    {
        return "Batch:".$this->operation.$this->location;
    }

    /**
     * @param self|null $a
     * @param self|null $b
     * @return bool True if $a is same as $b
     */
    public static function compare($a, $b)
    {
        if (!is_null($a) && !$a instanceof self) {
            throw new \InvalidArgumentException('$a should a location or null');
        }
        if (!is_null($b) && !$b instanceof self) {
            throw new \InvalidArgumentException('$b should a location or null');
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
