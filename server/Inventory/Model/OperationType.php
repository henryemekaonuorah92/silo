<?php

namespace Silo\Inventory\Model;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="operation_type")
 * @ORM\Entity(repositoryClass="Silo\Inventory\Repository\OperationTypeRepository")
 */
class OperationType
{
    /**
     * @var int
     *
     * @ORM\Column(name="operation_type_id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @ORM\Column(type="string", nullable=false)
     */
    private $name;

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }
}
