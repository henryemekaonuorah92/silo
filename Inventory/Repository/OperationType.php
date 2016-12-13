<?php

namespace Silo\Inventory\Repository;

use Doctrine\ORM\EntityRepository;
use Silo\Inventory\Model\OperationType as Model;
use Doctrine\ORM\Mapping;

class OperationType extends EntityRepository
{
    /**
     * {@inheritdoc}
     */
    public function __construct($em, Mapping\ClassMetadata $class)
    {
        parent::__construct($em, $class);
    }

    /**
     * Retrieve System location by code. If does not exist, create it.
     *
     * @return Model
     */
    public function getByName($name)
    {
        $instance = $this->findOneByName($name);
        if (! $instance) {
            $instance = new Model($name);
            $this->_em->persist($instance);
            $this->_em->flush($instance);
        }

        return $instance;
    }
}
