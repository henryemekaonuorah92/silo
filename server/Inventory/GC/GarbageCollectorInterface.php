<?php

namespace Silo\Inventory\GC;

interface GarbageCollectorInterface
{
    /**
     * @param \DateTime $horizon
     * @return int Number of lines actually removed from database
     */
    public function collect(\DateTime $horizon);
}