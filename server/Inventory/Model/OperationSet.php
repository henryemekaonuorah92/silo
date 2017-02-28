<?php

namespace Silo\Inventory\Model;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="Silo\Inventory\Repository\ContextRepository")
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
     */
    private $user;

    /**
     * @ORM\ManyToMany(targetEntity="Silo\Inventory\Model\Operation")
     * @ORM\JoinTable(name="operation_set_operations",
     *      joinColumns={@ORM\JoinColumn(name="operation_set_id", referencedColumnName="operation_set_id")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="operation_id", referencedColumnName="operation_id")}
     *      )
     */
    private $operations;

    public function __construct(User $user = null)
    {
        $this->user = $user;
        $this->operations = new ArrayCollection();
    }

    /**
     * {@inheritdoc}
     */
    public function __toString()
    {
        return 'Context';
    }

    /**
     * @return mixed
     * @todo Context Type is given by included Operation type...
     */
    public function getName()
    {
        throw new \Exception("What that sound ?");
        // return $this->type->getName();
    }

    /**
     * @return ArrayCollection
     */
    public function getOperations()
    {
        return $this->operations;
    }

    public function add(Operation $operation)
    {
        $operation->addOperationSet($this);
        $this->operations->add($operation);
    }

    public function isEmpty()
    {
        return $this->operations->count() == 0;
    }

    public function clear()
    {
        $this->operations->clear();
    }

    public function addSet(self $set)
    {
        foreach($set->getOperations() as $operation) {
            $this->add($operation);
        }
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    public function getItemCount()
    {
        $sum = function($a, $b){return $a+$b;};
        return array_reduce(array_map(function(Operation $operation)use($sum){
            array_reduce(array_map(function(Batch $batch){
                    return $batch->getQuantity();
            }, $operation->getBatches()->toArray()), $sum);
        }, $this->operations->toArray()), $sum);
    }

    public function getBatches()
    {
        $batches = new BatchCollection();

        foreach ($this->operations->toArray() as $operation) {
            $batches->merge($operation->getBatches());
        }

        return $batches;
    }
}
