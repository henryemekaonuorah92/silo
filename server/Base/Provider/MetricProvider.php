<?php
namespace Silo\Base\Provider;

use Pimple\Container;
use Silex\Api\EventListenerProviderInterface;
use Pimple\ServiceProviderInterface;
use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpKernel\KernelEvents;

class MetricProvider implements ServiceProviderInterface, EventListenerProviderInterface
{
    /**
     * {@inheritdoc}
     */
    public function register(\Pimple\Container $app)
    {
        $app['collector.type'] = 'null';
        $app['collector.configuration'] = [];

        $app['collector'] = function ($app) {
            return \Beberlei\Metrics\Factory::create(
                $app['collector.type'],
                $app['collector.configuration']
            );
        };
    }

    public function subscribe(Container $app, EventDispatcherInterface $dispatcher)
    {
        // Event has no typehint as it can be either a PostResponseEvent or a ConsoleTerminateEvent
        $onTerminate = function ($event) use ($app) {
            $app['collector']->flush();
        };

        $dispatcher->addListener(KernelEvents::TERMINATE, $onTerminate);

        if (class_exists('Symfony\Component\Console\ConsoleEvents')) {
            $dispatcher->addListener(ConsoleEvents::TERMINATE, $onTerminate);
        }
    }
}
