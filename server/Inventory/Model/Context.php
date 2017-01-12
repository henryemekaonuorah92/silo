<?php

namespace Silo\Inventory\Model;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="Silo\Inventory\Repository\Context")
 * @ORM\Table(name="context")
 */
class Context
{
    /**
     * @var int
     *
     * @ORM\Column(name="context_id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="ContextType")
     * @ORM\JoinColumn(name="context_type_id", referencedColumnName="context_type_id")
     */
    private $type;

    /**
     * @ORM\Column(name="value", type="integer")
     */
    private $value;

    /**
     * @ORM\ManyToMany(targetEntity="Silo\Inventory\Model\Operation")
     * @ORM\JoinTable(name="context_operation",
     *      joinColumns={@ORM\JoinColumn(name="context_id", referencedColumnName="context_id")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="operation_id", referencedColumnName="operation_id")}
     *      )
     */
    private $operations;

    public function __construct(ContextType $type, $value)
    {
        $this->type = $type;
        $this->value = $value;
        $this->operations = new ArrayCollection();
    }

    /**
     * {@inheritdoc}
     */
    public function __toString()
    {
        return 'Context:'.$this->type->getName().':'.$this->value;
    }

    /**
     * @return mixed
     */
    public function getName()
    {
        return $this->type->getName();
    }

    /**
     * @return mixed
     */
    public function getValue()
    {
        return $this->value;
    }

    public function addOperation(Operation $operation)
    {
        $this->operations->add($operation);
    }
}
