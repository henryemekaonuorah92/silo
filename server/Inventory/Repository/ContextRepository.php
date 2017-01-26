<?php

namespace Silo\Inventory\Repository;

use Doctrine\ORM\EntityRepository;
use Silo\Inventory\Model\Context as Model;
use Silo\Inventory\Model\ContextType;
use Doctrine\ORM\Mapping;
use Silo\Inventory\Model\User;

class ContextRepository extends EntityRepository
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

    public function create($name, $value, User $user = null)
    {
        $type = $this->_em->getRepository('Inventory:ContextType')
            ->findOneBy(['name' => $name]);
        if (!$type) {
            $type = call_user_func($this->createContextType, $name);
            $this->_em->persist($type);
            $this->_em->flush($type);
        }

        return new Model($type, $value, $user);
    }

    /**
     * @return Model
     * @todo refactor this
     */
    public function spawn($name, $value, User $user = null)
    {
        $type = $this->_em->getRepository('Inventory:ContextType')
            ->findOneBy(['name' => $name]);
        if (!$type) {
            $type = call_user_func($this->createContextType, $name);
            $this->_em->persist($type);
            $this->_em->flush($type);
        }

        $instance = $this->findOneBy(['type' => $type, 'value' => $value, 'user' => $user]);
        if (!$instance) {
            $instance = new Model($type, $value, $user);
        }

        return $instance;
    }
}
