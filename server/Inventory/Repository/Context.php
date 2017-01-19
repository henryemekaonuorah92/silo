<?php

namespace Silo\Inventory\Repository;

use Doctrine\ORM\EntityRepository;
use Silo\Inventory\Model\Context as Model;
use Silo\Inventory\Model\ContextType;
use Doctrine\ORM\Mapping;

class Context extends EntityRepository
{
    private $createContextType;

    /**
     * {@inheritdoc}
     */
    public function __construct($em, Mapping\ClassMetadata $class)
    {
        parent::__construct($em, $class);

        $this->createContextType = \Closure::bind(
            function ($name) {
                $type = new ContextType();
                $type->{'name'} = $name;

                return $type;
            },
            null,
            ContextType::class
        );
    }

    /**
     * @return Model
     */
    public function spawn($name, $value)
    {
        $type = $this->_em->getRepository('Inventory:ContextType')
            ->findOneBy(['name' => $name]);
        if (!$type) {
            $type = call_user_func($this->createContextType, $name);
            $this->_em->persist($type);
            $this->_em->flush($type);
        }

        $instance = $this->findOneBy(['type' => $type, 'value' => $value]);
        if (!$instance) {
            $instance = new Model($type, $value);
        }

        return $instance;
    }
}
