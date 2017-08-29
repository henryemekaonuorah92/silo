<?php

namespace Silo\Inventory\Model;

interface MarshallableInterface
{
    /**
     * @return mixed A jsonified representation of $this, suitable for transmitting inside a JsonResponse
     */
    public function marshall();
}