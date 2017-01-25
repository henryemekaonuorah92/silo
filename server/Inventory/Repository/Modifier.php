<?php

namespace Silo\Inventory\Repository;

use Doctrine\ORM\EntityRepository;
use Silo\Inventory\Model\Location as LocationModel;
use Silo\Inventory\Model\Modifier as Model;
use Silo\Inventory\Model\ModifierType;
use Doctrine\ORM\Mapping;

class Modifier extends EntityRepository
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
    public function add(LocationModel $location, $name, $value = null)
    {
        $type = $this->_em->getRepository('Inventory:ModifierType')
            ->findOneBy(['name' => $name]);
        if (!$type) {
            $type = call_user_func($this->createModifierType, $name);
            $this->_em->persist($type);
            $this->_em->flush($type);
        }

        $instance = $this->findOneBy(['type' => $type, 'location' => $location]);
        if (!$instance) {
            $instance = new Model($type, $location, $value);
            $this->_em->persist($instance);
        } else {
            $instance->setValue($value);
        }
    }

    /**
     * @todo be defensive about name and value
     */
    public function remove(LocationModel $location, $name)
    {
        $type = $this->_em->getRepository('Inventory:ModifierType')
            ->findOneBy(['name' => $name]);
        if ($type) {
            $instance = $this->findOneBy(['type' => $type, 'location' => $location]);
            if ($instance) {
                $this->_em->remove($instance);
            }
        }
    }
}
