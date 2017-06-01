<?php

namespace Silo\Context;

interface AppAwareContextInterface
{
    public function setApp(\Silex\Application $app);
}
