<?php

namespace Silo\Base;

use Pimple\Container;

trait ContainerAwareTrait
{
    /**
     * @var Container
     */
    protected $container;

    /**
     * Sets the container.
     *
     * @param Container|null $container A Container instance or null
     */
    public function setContainer(Container $container = null)
    {
        $this->container = $container;
    }
}
