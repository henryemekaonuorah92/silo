<?php

namespace Silo\Inventory\Repository;

use Doctrine\ORM\EntityRepository;
use Silo\Inventory\Model\Location as Model;

class Location extends EntityRepository
{
    /**
     * Retrieve System location by code. If does not exist, create it.
     *
     * @return Model
     */
    public function getSystemLocation($code)
    {
        if (!in_array($code, [Model::CODE_ROOT])) {
            throw new \InvalidArgumentException("$code is not a known Location");
        }

        $location = $this->findOneBy(['code' => $code]);
        if (!$location) {
            $location = new Model($code);
            $this->_em->persist($location);
            $this->_em->flush();
        }

        return $location;
    }
}
