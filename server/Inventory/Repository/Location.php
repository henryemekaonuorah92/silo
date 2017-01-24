<?php

namespace Silo\Inventory\Repository;

use Doctrine\ORM\EntityRepository;
use Silo\Inventory\LocationWalker;
use Silo\Inventory\Model\BatchCollection;
use Silo\Inventory\Model\Location as Model;
use Silo\Inventory\Model\Operation as OperationModel;
use Silo\Inventory\Model\User as UserModel;

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
     *
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

    public function spawnLocation($code, $parentCode, UserModel $user, $operationTypeName)
    {
        $location = $this->findOneByCode($code);
        if ($location) {
            return $location;
        }

        $parentLocation = $this->findOneByCode($parentCode);
        if (!$parentLocation) {
            throw new \Exception("Parent Location:$parentCode does not exist");
        }

        $location = new \Silo\Inventory\Model\Location($code);
        $this->_em->persist($location); $this->_em->flush();

        $operation = new OperationModel($user, null, $parentLocation, $location);
        $operation->setType(
            $this->_em->getRepository('Inventory:OperationType')->getByName($operationTypeName)
        );
        $this->_em->persist($operation);
        $this->_em->flush();

        $operation->execute($user);
        $this->_em->flush();

        return $location;
    }

    /**
     * @param $poolName
     * @return Model|null
     * @throws \Exception
     */
    public function findSpawnPool($poolName)
    {
        $query = $this->_em->createQueryBuilder();
        $query->select('modifier, location')
            ->from('Inventory:Modifier', 'modifier')
            ->join('modifier.location', 'location')
            ->join('modifier.type', 'type')
            ->andWhere('type.name = :type')
            ->andWhere('modifier.value = :poolName')
            ->setParameter('type', 'spawnPool')
            ->setParameter('poolName', $poolName);

        $modifiers = $query->getQuery()->getResult();

        if (count($modifiers) > 1) {
            throw new \Exception("There can be only one spawnPool for $poolName");
        }

        return !empty($modifiers) ? $modifiers[0]->getLocation() : null;
    }
}
