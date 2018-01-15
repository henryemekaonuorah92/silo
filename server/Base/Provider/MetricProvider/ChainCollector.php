<?php

namespace Silo\Base\Provider\MetricProvider;

use Beberlei\Metrics\Collector as C;

class ChainCollector implements C\Collector, C\GaugeableCollector
{
    protected $collectors;

    public function __construct(array $collectors = [])
    {
        $this->collectors = $collectors;
    }

    public function measure($variable, $value)
    {
        foreach($this->collectors as $c){
            $c->measure($variable, $value);
        }
    }

    public function increment($variable)
    {
        foreach($this->collectors as $c){
            $c->increment($variable);
        }
    }

    public function decrement($variable)
    {
        foreach($this->collectors as $c){
            $c->decrement($variable);
        }
    }

    public function timing($variable, $time)
    {
        foreach($this->collectors as $c){
            $c->timing($variable, $time);
        }
    }

    public function flush()
    {
        foreach($this->collectors as $c){
            $c->flush();
        }
    }

    public function gauge($variable, $value)
    {
        foreach($this->collectors as $c){
            if ($c instanceof C\GaugeableCollector) {
                $c->gauge($variable, $value);
            }
        }
    }
}
