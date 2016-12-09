<?php

namespace Silo\Base\Provider;

use Doctrine\Common\Cache\ArrayCache;
use Doctrine\Common\Cache\FilesystemCache;
use Silo\Base\Provider\DoctrineProvider\TablePrefix;
use Pimple\ServiceProviderInterface;
use Doctrine\ORM\Tools\Setup;
use Doctrine\ORM\EntityManager;

class DoctrineProvider implements ServiceProviderInterface
{
    private $modelPaths;

    public function __construct($modelPaths)
    {
        $this->modelPaths = $modelPaths;
    }

    public function register(\Pimple\Container $app)
    {
        if (!isset($app['em.dsn'])) {
            throw new \Exception("Please provide em.dsn");
        }

        $cache = new ArrayCache(); //new FilesystemCache(sys_get_temp_dir());
        $paths = $this->modelPaths;

        $app['em'] = function () use ($app, $cache, $paths) {
            $config = Setup::createAnnotationMetadataConfiguration(
                $paths, false, null, $cache, false
            );
            $config->addEntityNamespace('Inventory', 'Silo\Inventory\Model');

            $evm = new \Doctrine\Common\EventManager;
            $evm->addEventListener(\Doctrine\ORM\Events::loadClassMetadata, new TablePrefix('silo_'));

            return EntityManager::create(['url' => $app['em.dsn']], $config, $evm);
        };
    }
}
