<?php

namespace Silo\Inventory\Repository;

use Doctrine\ORM\EntityRepository;
use Silo\Inventory\Model\Location;
use Silo\Inventory\Model\Operation as Model;

class Operation extends EntityRepository
{
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
