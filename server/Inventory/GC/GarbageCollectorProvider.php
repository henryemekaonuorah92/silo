<?php

namespace Silo\Inventory\GC;

use Pimple\Container;
use Pimple\ServiceProviderInterface;
use Silex\Api\EventListenerProviderInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class GarbageCollectorProvider implements ServiceProviderInterface, EventListenerProviderInterface
{
    const EVENT_GARBAGE_COLLECT = 'silo.garbage_collect';

    /**
     * {@inheritdoc}
     */
    public function register(\Pimple\Container $app)
    {
        $app['GarbageCollector'] = function () use ($app) {
            $s = new BatchGarbageCollector();
            $s->setEntityManager($app['em']);
            $s->setLogger($app['logger']);
            return $s;
        };
    }

    public function subscribe(Container $app, EventDispatcherInterface $dispatcher)
    {
        $dispatcher->addListener(self::EVENT_GARBAGE_COLLECT, function() use ($app){
            $app['GarbageCollector']->collect();
        });
    }
}
