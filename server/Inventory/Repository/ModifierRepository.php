<?php

namespace Silo\Inventory\Repository;

use Doctrine\ORM\EntityRepository;
use Silo\Inventory\Model\Location;
use Silo\Inventory\Model\Modifier;
use Silo\Inventory\Model\ModifierType;
use Doctrine\ORM\Mapping;

class ModifierRepository extends EntityRepository
{
    private $createModifierType;

    /**
     * {@inheritdoc}
     */
    public function __construct($em, Mapping\ClassMetadata $class)
    {
        parent::__construct($em, $class);

        $this->createModifierType = \Closure::bind(
            function ($name) {
                $type = new ModifierType();
                $type->{'name'} = $name;

                return $type;
            },
            null,
            ModifierType::class
        );
    }

    /**
     * @todo be defensive about name and value
     */
    public function add(Location $location, $name, $value = null)
    {
        $type = $this->getType($name);

        $instance = $this->findOneBy(['type' => $type, 'location' => $location]);
        if (!$instance) {
            $instance = new Modifier($type, $location, $value);
            $this->_em->persist($instance);
        } else {
            $instance->setValue($value);
        }
    }

    private $typeCache = [];

    public function getType($name)
    {
        if (!isset($this->typeCache[$name]) || !$this->_em->contains($this->typeCache[$name])) {
            $type = $this->_em->getRepository(ModifierType::class)
                ->findOneBy(['name' => $name]);
            if (!$type) {
                $type = call_user_func($this->createModifierType, $name);
                $this->_em->persist($type);
                $this->_em->flush($type);
            }

            $this->typeCache[$name] = $type;
        }

        return $this->typeCache[$name];
    }

    /**
     * @todo be defensive about name and value
     */
    public function remove(Location $location, $name)
    {
        $type = $this->_em->getRepository(ModifierType::class)
            ->findOneBy(['name' => $name]);
        if ($type) {
            $instance = $this->findOneBy(['type' => $type, 'location' => $location]);
            if ($instance) {
                $this->_em->remove($instance);
            }
        }
    }
}
