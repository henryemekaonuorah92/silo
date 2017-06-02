<?php

namespace Silo\Context;

trait AppAwareContextTrait
{
    protected $app;

    public function setApp(\Silex\Application $app)
    {
        $this->app = $app;
    }
}
