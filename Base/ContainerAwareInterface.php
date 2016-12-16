<?php

namespace Silo\Base;

use Pimple\Container;

interface ContainerAwareInterface
{
    public function setContainer(Container $container = null);
}