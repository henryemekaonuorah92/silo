<?php

interface AppAwareContextInterface
{
    public function setApp(\Silex\Application $app);
}