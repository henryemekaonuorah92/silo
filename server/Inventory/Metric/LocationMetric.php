<?php

namespace Silo\Inventory\Metric;

use Beberlei\Metrics\Collector\Collector;
use Beberlei\Metrics\Collector\GaugeableCollector;
use Pimple\Container;
use Silo\Base\MetricInterface;
use Silo\Inventory\Model\Location;
use Silo\Inventory\Model\Operation;

/**
 * @metric gauge location.<active/deleted> Count of Locations in the database
 * @metric gauge location.modifier.<modifier> Count of Locations with Modifier in the database
 */
class LocationMetric implements MetricInterface
{
    public function measure(Container $container, Collector $collector)
    {
        if (! $collector instanceof GaugeableCollector) {
            return;
        }

        $q = $container['em']->createQueryBuilder();
        $q->select('type.name, COUNT(l.id) as cnt')
            ->from(Location::class, 'l')
            ->leftJoin('l.modifiers', 'mod')
            ->leftJoin('mod.type', 'type')
            ->groupBy('type.id');

        $results = $q->getQuery()->getArrayResult();
        foreach ($results as $r) {
            if (empty($r['name'])) {
                $r['name'] = 'null';
            }
            $type = str_replace(['-', ' ', '.'], '_', $r['name']);
            $collector->gauge('location.modifier.'.$type, $r['cnt']);
        }

        $q = $container['em']->createQueryBuilder();
        $q->select('COUNT(l.id)')
            ->from(Location::class, 'l')
            ->where('l.isDeleted = 1');
        $collector->gauge('location.deleted', $q->getQuery()->getSingleScalarResult());

        $q = $container['em']->createQueryBuilder();
        $q->select('COUNT(l.id)')
            ->from(Location::class, 'l')
            ->where('l.isDeleted = 0');
        $collector->gauge('location.active', $q->getQuery()->getSingleScalarResult());
    }
}
