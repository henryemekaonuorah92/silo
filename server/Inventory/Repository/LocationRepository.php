<?php

namespace Silo\Inventory\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
use Silo\Inventory\Finder\OperationFinder;
use Silo\Inventory\LocationWalker;
use Silo\Inventory\Collection\BatchCollection;
use Silo\Inventory\Model\Location;
use Silo\Inventory\Model\Modifier;
use Silo\Inventory\Model\Operation as OperationModel;
use Silo\Inventory\Model\User as UserModel;
use Silo\Inventory\Model\User;
use SiloLink\SiloBridge;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

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
        $walker = new LocationWalker($this->_em, function (QueryBuilder $query) {
            $query->addSelect('Batches, Product')
                ->join('Location.batches', 'Batches')
                ->join('Batches.product', 'Product');
        });

        return $walker->mapReduce(
            $location,
            function (Location $l) {
                return $l->getBatches()->copy();
            },
            function ($a, $b) {
                return $a->merge($b, true)->copy();
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
     * @deprecated Use something else
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
        $this->_em->persist($location);
        $this->_em->flush();

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

    public function spawnLocationNoFlush($code, $parentCode, UserModel $user, $operationTypeName)
    {
        $type = $this->_em->getRepository('Inventory:OperationType')->getByName($operationTypeName);

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
        $this->_em->persist($location);

        $operation = new OperationModel($user, null, $parentLocation, $location);
        $operation->setType($type);
        $operation->execute($user);
        $this->_em->persist($operation);

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
            ->from(Modifier::class, 'modifier')
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

    public function delete(Location $location, User $user)
    {
        if ($location->isDeleted()) {
            throw new \LogicException("$location is already deleted");
        }
        if (count($location->getChildren()) > 0) {
            throw new \LogicException("Cannot delete $location because it has children");
        }

        // Cancel all pending operations
        $finder = new OperationFinder($this->_em);
        $operations = $finder->manipulating($location)->isPending()->find();
        foreach ($operations as $operation) {
            $operation->cancel($user);
        }

        // Delete location
        $this->_em->getRepository(\Silo\Inventory\Model\Operation::class)
            ->executeOperation($user, $location->getParent(), null, 'delete location', $location);

        $this->_em->flush();
    }

    public function respawn(Location $location, User $user)
    {
        if (!$location->isDeleted()) {
            throw new \LogicException("$location is not deleted");
        }

        // Get the latest deletion operation
        // Cancel all pending operations
        $finder = new OperationFinder($this->_em);
        $operations = $finder->manipulating($location)->isDone()->isType('delete location')->find();

        if (count($operations) < 1) {
            throw new \LogicException("$location cannot be brought back");
        }

        $lastDelOp = array_pop($operations);

        // Undelete location
        $this->_em->getRepository(\Silo\Inventory\Model\Operation::class)
            ->executeOperation($user, null, $lastDelOp->getSource(), 'respawn location', $location);

        $this->_em->flush();
    }

    public function getProvider($noDelete = true)
    {
        $locations = $this;
        return function ($code) use ($locations, $noDelete) {
            $location = $locations->findOneByCode($code);
            if (!$location || ($location->isDeleted() && $noDelete)) {
                throw new NotFoundHttpException("Location $code cannot be found");
            }

            return $location;
        };
    }
}
