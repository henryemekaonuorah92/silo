<?php

namespace Silo\Inventory\Model;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Silo\Inventory\Collection\OperationCollection;

/**
 * @ORM\Entity
 * @ORM\Table(name="operation_set")
 */
class OperationSet
{
    /**
     * @var int
     *
     * @ORM\Column(name="operation_set_id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="User")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="user_id")
     * @todo User is not needed here !
     */
    private $user;

    /**
     * @var OperationCollection
     * @ORM\ManyToMany(targetEntity="Silo\Inventory\Model\Operation", inversedBy="operationSets", cascade={"persist"})
     * @ORM\JoinTable(name="operation_set_operations",
     *      joinColumns={@ORM\JoinColumn(name="operation_set_id", referencedColumnName="operation_set_id")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="operation_id", referencedColumnName="operation_id")}
     *      )
     */
    private $operations;

    /**
     * @ORM\Column(name="value", type="json_array", nullable=true)
     */
    private $value;

    private $merged = false;

    public function __construct(User $user = null, $value = null)
    {
        $this->user = $user;
        $this->value = $value;
        $this->operations = new OperationCollection();
    }

    /** @return int */
    public function getId()
    {
        return $this->id;
    }

    /** {@inheritdoc} */
    public function __toString()
    {
        $str = 'OperationSet:'.$this->id.'(';
        foreach($this->operations as $op){
            $str.="+$op";
        }
        return $str.')['.$this->value.']';
    }

    public function getTypes()
    {
        return $this->operations->getTypes();
    }

    /**
     * @return OperationCollection Copy of the contained Operations. You can manipulate this copy as you wish,
     * it won't affect the relationships.
     */
    public function getOperations()
    {
        return OperationCollection::fromCollection($this->operations);
    }

    public function add(Operation $operation)
    {
        $operation->addOperationSet($this);
        $this->operations->add($operation);
    }

    /**
     * @param Operation $operation
     * @todo clear the owning problem here
     */
    public function addOwningOnly(Operation $operation)
    {
        $this->operations->add($operation);
    }

    public function addSet(self $set)
    {
        foreach ($set->getOperations()->toArray() as $operation) {
            $this->add($operation);
        }
    }

    public function remove(Operation $operation)
    {
        $operation->removeOperationSet($this);
        $this->operations->removeElement($operation);
    }

    public function isEmpty()
    {
        return $this->operations->isEmpty();
    }

    public function clear()
    {
        foreach ($this->operations as $op) {
            $this->remove($op);
        }
    }

    /**
     * @param OperationSet $set to be merged into $this
     */
    public function merge(self $set)
    {
        if ($this->merged) {
            throw new \LogicException("You cannot remerge an OperationSet");
        }

        foreach($set->getOperations() as $operation) {
            $set->remove($operation);
            $this->add($operation);
        }

        $this->merged = true;
    }

    /**
     * @param mixed $value
     */
    public function setValue($value)
    {
        $this->value = $value;
    }

    /**
     * @return mixed Value that will be attached to this OperationSet. It has to be json-ifiable.
     */
    public function getValue()
    {
        return $this->value;
    }
}
