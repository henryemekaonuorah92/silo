<?php

namespace Silo\Base\Model;

/**
 * Can be garbage collected
 */
interface Collectable
{
    public function isCollectable();
}