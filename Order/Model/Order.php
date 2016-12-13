<?php

namespace Silo\Order\Model;

use Doctrine\ORM\Mapping as ORM;
use Silo\Inventory\Model\Operation;

/**
 * @ORM\Entity
 * @ORM\Table(name="order")
 */
class Order
{
    /**
     * @var int
     *
     * @ORM\Id
     * @ORM\Column(name="order_id", type="integer")
     * @ORM\GeneratedValue
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="external_id", type="string", length=255)
     */
    private $externalId;

    /**
     * @ORM\ManyToMany(targetEntity="Silo\Inventory\Model\Operation")
     * @ORM\JoinTable(name="order_operation",
     *      joinColumns={@ORM\JoinColumn(name="order_id", referencedColumnName="order_id")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="operation_id", referencedColumnName="operation_id")}
     *      )
     */
    private $operations;

    public function __construct()
    {
        $this->operations = new \Doctrine\Common\Collections\ArrayCollection();
    }

    public function addOperation(Operation $operation)
    {
        $this->operations->add($operation);
    }

    /**
     * @return string
     */
    public function getExternalId()
    {
        return $this->externalId;
    }

    /**
     * @param string $externalId
     */
    public function setExternalId($externalId)
    {
        $this->externalId = $externalId;
    }
}