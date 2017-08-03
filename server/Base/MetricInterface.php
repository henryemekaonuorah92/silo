<?php

namespace Silo\Base;

use Beberlei\Metrics\Collector\Collector;
use Pimple\Container;

interface MetricInterface
{
    public function measure(Container $container, Collector $collector);
}
