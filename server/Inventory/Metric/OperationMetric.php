<?php

namespace Silo\Inventory\Metric;

use Beberlei\Metrics\Collector\Collector;
use Beberlei\Metrics\Collector\GaugeableCollector;
use Pimple\Container;
use Silo\Base\MetricInterface;
use Silo\Inventory\Model\Operation;

/**
 * @metric gauge operation.type.<type> Number of operations of given type
 */
class OperationMetric implements MetricInterface
{
    public function measure(Container $container, Collector $collector)
    {
        if (! $collector instanceof GaugeableCollector) {
            return;
        }
        $q = $container['em']->createQueryBuilder();
        $q->select('type.name, COUNT(o.id) as cnt')
            ->from(Operation::class, 'o')
            ->join('o.operationType', 'type')
            ->groupBy('type.id');

        $results = $q->getQuery()->getArrayResult();
        foreach ($results as $r) {
            $type = str_replace(['-', ' ', '.'], '_', $r['name']);
            $collector->gauge('operation.type.'.$type, $r['cnt']);
        }
    }
}
