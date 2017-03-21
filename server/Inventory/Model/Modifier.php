<?php

namespace Silo\Inventory\Model;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\UniqueConstraint;
use Doctrine\ORM\Mapping\Index;

/**
 * @ORM\Entity(repositoryClass="Silo\Inventory\Repository\ModifierRepository")
 * @ORM\Table(
 *     name="modifier",
 *     uniqueConstraints={
 *         @UniqueConstraint(name="location_type_idx", columns={"location", "modifier_type_id"})
 *     }
 * )
 */
class Modifier
{
    /**
     * @var int
     *
     * @ORM\Column(name="modifier_id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="ModifierType")
     * @ORM\JoinColumn(name="modifier_type_id", referencedColumnName="modifier_type_id")
     */
    private $type;

    /**
     * @ORM\Column(name="value", type="json_array", nullable=true)
     */
    private $value;

    /**
     * @ORM\ManyToOne(targetEntity="Location")
     * @ORM\JoinColumn(name="location", referencedColumnName="location_id")
     */
    private $location;

    public function __construct(ModifierType $type, Location $location, $value = null)
    {
        $this->type = $type;
        $this->location = $location;
        $this->value = $value;

        // @todo ModifierType may format/validate the value :)
    }

    /**
     * {@inheritdoc}
     */
    public function __toString()
    {
        return 'Modifier:'.$this->type->getName();
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

    /**
     * @param mixed $value
     */
    public function setValue($value)
    {
        $this->value = $value;
    }

    /**
     * @return mixed
     */
    public function getLocation()
    {
        return $this->location;
    }
}
