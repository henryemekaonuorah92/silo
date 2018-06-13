<?php

namespace Silo\Inventory\Repository;

use Doctrine\ORM\EntityRepository;
use Silo\Inventory\Model\Location;
use Silo\Inventory\Model\ModifierType;
use Silo\Inventory\Model\Operation as Model;
use Silo\Inventory\Model\Operation;
use Silo\Inventory\Model\User;

class OperationRepository extends EntityRepository
{
    public function createLocationAt(User $user, Location $parent, Location $location, ModifierType $type = null)
    {
        $operation = new Model($user, null, $parent, $location);
        if ($type) {
            $operation->setType($type);
        }
        $operation->execute($user);
        $this->_em->persist($operation);

        return $operation;
    }

    public function executeOperation($user, $from, $to, $type, $content)
    {
        $locations = $this->_em->getRepository('Inventory:Location');

        if (!$from instanceof Location) {
            $from = $from ? $locations->findOneByCode($from) : null;
        }
        if (!$to instanceof Location) {
            $to = $to ? $locations->findOneByCode($to) : null;
        }

        $operation = new Model($user, $from, $to, $content);
        $operation->setType(
            $this->_em->getRepository('Inventory:OperationType')->getByName($type)
        );
        $this->_em->persist($operation);
        $this->_em->flush();

        $operation->execute($user);
        $this->_em->flush();
    }
}
