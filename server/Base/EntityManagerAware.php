<?php

namespace Silo\Base;

use Doctrine\ORM\EntityManager;

interface EntityManagerAware
{
    public function setEntityManager(EntityManager $em);
}
