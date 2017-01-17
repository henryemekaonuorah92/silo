<?php

namespace Silo\Base\Provider;

use Doctrine\Common\Cache\ArrayCache;
use Silo\Base\Provider\DoctrineProvider\SQLLogger;
use Silo\Base\Provider\DoctrineProvider\TablePrefix;
use Pimple\ServiceProviderInterface;
use Doctrine\ORM\Tools\Setup;
use Doctrine\ORM\EntityManager;
use Doctrine\DBAL\Types\Type;
use Silo\Base\Provider\DoctrineProvider\UTCDateTimeType;

/**
 * Doctrine ORM as a Service.
 */
class DoctrineProvider implements ServiceProviderInterface
{
    /** @var string[] */
    private $modelPaths;

    /**
     * @param string[] $modelPaths Models path in the filesystem
     */
    public function __construct($modelPaths)
    {
        $this->modelPaths = $modelPaths;
    }

    /**
     * {@inheritdoc}
     */
    public function register(\Pimple\Container $app)
    {
        if (!isset($app['em.dsn'])) {
            throw new \Exception('Please provide em.dsn');
        }

        // @todo implement a better cache selection mechanism
        $cache = new ArrayCache(); //new FilesystemCache(sys_get_temp_dir());
        $paths = $this->modelPaths;

        // UTC for datetimes
        Type::overrideType('datetime', UTCDateTimeType::class);
        Type::overrideType('datetimetz', UTCDateTimeType::class);

        $app['em_logger'] = function () use ($app) {
            return new SQLLogger();
        };

        $app['em'] = function () use ($app, $cache, $paths) {
            $config = Setup::createAnnotationMetadataConfiguration(
                $paths, false, null, $cache, false
            );
            $config->addEntityNamespace('Inventory', 'Silo\Inventory\Model');
            $config->setSQLLogger($app['em_logger']);

            $evm = new \Doctrine\Common\EventManager();
            $evm->addEventListener(\Doctrine\ORM\Events::loadClassMetadata, new TablePrefix('silo_'));

            return EntityManager::create(['url' => $app['em.dsn']], $config, $evm);
        };
    }
}
