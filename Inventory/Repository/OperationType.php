<?php

namespace Silo\Inventory\Repository;

use Doctrine\ORM\EntityRepository;
use Silo\Inventory\Model\OperationType as Model;
use Doctrine\ORM\Mapping;

class OperationType extends EntityRepository
{
    private $createOperationType;

    /**
     * {@inheritdoc}
     */
    public function __construct($em, Mapping\ClassMetadata $class)
    {
        parent::__construct($em, $class);

        $this->createOperationType = \Closure::bind(
            function ($name) {
                $type = new Model();
                $type->name = $name;
                return $type;
            },
            null,
            Model::class
        );
    }

    /**
     * Retrieve System location by code. If does not exist, create it.
     *
     * @return Model
     */
    public function getByName($name)
    {
        $instance = $this->findOneBy(['name' => $name]);
        if (! $instance) {
            $instance = call_user_func($this->createOperationType, $name);
            $this->_em->persist($instance);
            $this->_em->flush($instance);
        }

        return $instance;
    }
}
