<?php

namespace Silo\Inventory\Repository;

use Doctrine\ORM\EntityRepository;
use Silo\Inventory\LocationWalker;
use Silo\Inventory\Model\BatchCollection;
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

    /**
     * @param $location
     * @return \Silo\Inventory\Model\Batch[]
     */
    public function getInclusiveContent($location)
    {
        $walker = new LocationWalker($this->_em);

        return $walker->mapReduce(
            $location,
            function (Model $l) {
                return $l->getBatches();
            },
            function ($a, $b) {
                return $a->merge($b);
            },
            new BatchCollection()
        );
    }

    public function forceFindOneByCode($code)
    {
        $location = $this->findOneByCode($code);

        if (!$location) {
            throw new \Exception("Location $code does not exist");
        }

        return $location;
    }
}
