<?php
namespace Silo\Base\Provider;

use Beberlei\Metrics\Collector\Logger;
use Pimple\Container;
use Silex\Api\EventListenerProviderInterface;
use Pimple\ServiceProviderInterface;
use Silo\Base\Provider\MetricProvider\ChainCollector;
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
        if (!isset($app['collector.type'])) {
            $app['collector.type'] = 'null';
        }
        if (!isset($app['collector.configuration'])) {
            $app['collector.configuration'] = [];
        }

        $app['collector'] = function ($app) {
            return new ChainCollector([
                \Beberlei\Metrics\Factory::create(
                    $app['collector.type'],
                    $app['collector.configuration']
                ),
                new Logger($app['logger'])
            ]);
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
