<?php

namespace Silo\Base\Provider;

use Beberlei\Metrics\Collector\Collector;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Events;
use Silo\Inventory\Model\Operation;

class OperationSubscriber implements \Doctrine\Common\EventSubscriber
{
    /** @var Collector */
    private $collector;

    private $gotStuff = false;

    public function __construct(Collector $collector)
    {
        $this->collector = $collector;
    }

    /**
     * @param LifecycleEventArgs $event
     * @metric increment operation.new.<type>.count
     * @metric measure   operation.new.<type>.batchQuantity
     * @metric increment operation.new.<type>.locationCount
     */
    public function postPersist(LifecycleEventArgs $event)
    {
        $entity = $event->getEntity();
        if (!$entity instanceof Operation) {
            return;
        }

        $quantity = $entity->getBatches()->getQuantity();

        $type = strtr($entity->getType() ?: 'no_type', ' .','__');
        $prefix = sprintf('operation.pending.%s.', $type);
        $this->collector->increment($prefix.'count');
        if ($quantity > 0) {
            $this->collector->measure($prefix.'batchQuantity', $quantity);
        }
        if ($entity->isLocationOperation()) {
            $this->collector->increment($prefix.'locationCount');
        }
        $this->gotStuff = true;
    }

    public function postUpdate(LifecycleEventArgs $event)
    {
        $entity = $event->getEntity();
        if (!$entity instanceof Operation) {
            return;
        }

        $quantity = $entity->getBatches()->getQuantity();

        $type = strtr($entity->getType() ?: 'no_type', ' .','__');
        $isCancelled = $entity->getStatus()->isCancelled();
        $isExecuted = $entity->getStatus()->isDone();
        if (! $isCancelled && ! $isExecuted) {
            return;
        }
        $status = $isExecuted ? 'executed' : 'cancelled';
        $prefix = sprintf('operation.%s.%s.', $status, $type);
        $this->collector->increment($prefix.'count');
        if ($quantity > 0) {
            $this->collector->measure($prefix.'batchQuantity', $quantity);
        }
        if ($entity->isLocationOperation()) {
            $this->collector->increment($prefix.'locationCount');
        }
        $this->gotStuff = true;
    }

    public function postFlush()
    {
        if ($this->gotStuff) {
            $this->collector->flush();
            $this->gotStuff = false;
        }
    }

    public function getSubscribedEvents()
    {
        return [Events::postPersist, Events::postFlush, Events::postUpdate];
    }
}
