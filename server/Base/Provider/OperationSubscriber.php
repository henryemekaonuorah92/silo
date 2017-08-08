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

        $this->dealWithOperation($entity);
    }

    public function postUpdate(LifecycleEventArgs $event)
    {
        $entity = $event->getEntity();
        if (!$entity instanceof Operation) {
            return;
        }

        $this->dealWithOperation($entity);
    }

    private function dealWithOperation(Operation $op)
    {
        $quantity = $op->getBatches()->getQuantity();
        $type = strtr($op->getType() ?: 'no_type', ' .','__');
        $isCancelled = $op->getStatus()->isCancelled();
        $isExecuted = $op->getStatus()->isDone();

        $status = 'pending';
        if ($isCancelled || $isExecuted) {
            $status = $isExecuted ? 'executed' : 'cancelled';
        }

        $prefix = sprintf('operation.%s.%s.', $status, $type);
        $this->collector->increment($prefix.'count');
        if ($quantity > 0) {
            $this->collector->measure($prefix.'batchQuantity', $quantity);
        }
        if ($op->isLocationOperation()) {
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
