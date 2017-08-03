<?php
/**
 * Created by PhpStorm.
 * User: david
 * Date: 2017-07-04
 * Time: 2:53 PM
 */

namespace Silo\Base\Provider\DoctrineProvider;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Repository\DefaultRepositoryFactory;
use Silo\Context\AppAwareContextInterface;
use Pimple\Container;

class RepositoryFactory implements \Doctrine\ORM\Repository\RepositoryFactory
{
    /** @var Container The App */
    private $app;

    /**
     * @param Container $app The App
     */
    public function __construct(Container $app)
    {
        $this->app = $app;
    }

    /**
     * The list of EntityRepository instances.
     *
     * @var \Doctrine\Common\Persistence\ObjectRepository[]
     */
    private $repositoryList = array();

    /**
     * {@inheritdoc}
     */
    public function getRepository(EntityManagerInterface $entityManager, $entityName)
    {
        $repositoryHash = $entityManager->getClassMetadata($entityName)->getName() . spl_object_hash($entityManager);

        if (isset($this->repositoryList[$repositoryHash])) {
            return $this->repositoryList[$repositoryHash];
        }

        $repository = $this->repositoryList[$repositoryHash] = $this->createRepository($entityManager, $entityName);
        if ($repository instanceof AppAwareContextInterface) {
            $repository->setApp($this->app);
        }

        return $repository;
    }

    /**
     * Create a new repository instance for an entity class.
     *
     * @param \Doctrine\ORM\EntityManagerInterface $entityManager The EntityManager instance.
     * @param string                               $entityName    The name of the entity.
     *
     * @return \Doctrine\Common\Persistence\ObjectRepository
     */
    private function createRepository(EntityManagerInterface $entityManager, $entityName)
    {
        /* @var $metadata \Doctrine\ORM\Mapping\ClassMetadata */
        $metadata            = $entityManager->getClassMetadata($entityName);
        $repositoryClassName = $metadata->customRepositoryClassName
            ?: $entityManager->getConfiguration()->getDefaultRepositoryClassName();

        return new $repositoryClassName($entityManager, $metadata);
    }
}
