<?php

namespace Silo\Inventory\Model;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\UniqueConstraint;
use Doctrine\ORM\Mapping\Index;

/**
 * You shall not construct this Model by yourself. Use Silo\Inventory\Repository\Modifier.
 *
 * @ORM\Table(
 *     name="modifier_type",
 *     uniqueConstraints={
 *         @UniqueConstraint(columns={"name"})
 *     }
 * )
 * @ORM\Entity
 */
class ModifierType
{
    /**
     * @var int
     *
     * @ORM\Column(name="modifier_type_id", type="integer")
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
