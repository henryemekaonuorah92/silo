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
     * @ORM\Column(name="name", type="string", length=255)
     */
    private $name;

    /**
     * @var string
     *
     * @ORM\Column(name="sku", type="string", length=255)
     */
    private $sku;

    public function __construct($sku)
    {
        if (!is_string($sku)) {
            throw new \Exception("sku has to be a string");
        }
        $len = strlen($sku);
        if ($len == 0 || $len > 254) {
            throw new \Exception("sku has to be shorter than 255 characters, but not empty");
        }
        $this->sku = $sku;
    }

    /**
     * @return string
     */
    public function getSku()
    {
        return $this->sku;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    public function __toString()
    {
        return $this->sku;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }
}
