<?php

namespace Silo\Inventory\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
use Silo\Inventory\LocationWalker;
use Silo\Inventory\Collection\BatchCollection;
use Silo\Inventory\Model\Location;
use Silo\Inventory\Model\Operation as OperationModel;
use Silo\Inventory\Model\User as UserModel;

class LocationRepository extends EntityRepository
{
    /**
     * The root Location is the only Location that always exists. It is at the root (sic)
     * of the inventory Location tree.
     * @return Location
     */
    public function getRoot()
    {
        $location = $this->findOneBy(['code' => Location::CODE_ROOT]);
        if (!$location) {
            $location = new Location(Location::CODE_ROOT);
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
        $walker = new LocationWalker($this->_em, function(QueryBuilder $query){
            $query->addSelect('Batches, Product')
                ->join('Location.batches', 'Batches')
                ->join('Batches.product', 'Product');
        });

        return $walker->mapReduce(
            $location,
            function (Location $l) {
                return $l->getBatches();
            },
            function ($a, $b) {
                return $a->merge($b, true);
            },
            new BatchCollection()
        );
    }

    public function findOneByCode($code)
    {
        if ($code == Location::CODE_ROOT) {
            return $this->getRoot();
        }

        return parent::findOneBy(['code' => $code]);
    }

    public function forceFindOneByCode($code)
    {
        if ($code == Location::CODE_ROOT) {
            return $this->getRoot();
        }

        $location = $this->findOneByCode($code);

        if (!$location) {
            throw new \Exception("Location $code does not exist");
        }

        return $location;
    }

    /**
     * @todo move elsewhere
     */
    public function spawnLocation($code, $parentCode, UserModel $user, $operationTypeName)
    {
        $location = $this->findOneByCode($code);
        if ($location) {
            return $location;
        }

        if (!$parentCode instanceof Location) {
            $parentLocation = $this->findOneByCode($parentCode);
            if (!$parentLocation) {
                throw new \Exception("Parent Location:$parentCode does not exist");
            }
        } else {
            $parentLocation = $parentCode;
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
     * @todo move elsewhere
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
