<?php

namespace Silo\Inventory\GC;

use Pimple\Container;
use Pimple\ServiceProviderInterface;
use Silex\Api\EventListenerProviderInterface;
use Silo\Base\EntityManagerAware;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class GarbageCollectorProvider implements ServiceProviderInterface, EventListenerProviderInterface
{
    const EVENT_GARBAGE_COLLECT = 'gc.collect';

    /**
     * {@inheritdoc}
     */
    public function register(\Pimple\Container $app)
    {
        $app['gc.horizon'] = new \DateTime('-6 months');

        $app['gc.collectors'] = function () use ($app) {
            return [
                new BatchGarbageCollector(),
                new OperationGarbageCollector(),
            ];
        };
    }

    public function subscribe(Container $app, EventDispatcherInterface $dispatcher)
    {
        $dispatcher->addListener(self::EVENT_GARBAGE_COLLECT, function () use ($app) {
            foreach ($app['gc.collectors'] as $collector) {
                if ($collector instanceof EntityManagerAware) {
                    $collector->setEntityManager($app['em']);
                }

                // @todo maybe some probbing here
                $deletedCount = $collector->collect($app['gc.horizon']);
                if ($deletedCount > 0) {
                    $app['logger']->info(sprintf("%s garbage collected %s", get_class($collector), $deletedCount));
                } else {
                    $app['logger']->debug(sprintf("%s garbage collected nothing", get_class($collector)));
                }
            }
        });
    }
}
