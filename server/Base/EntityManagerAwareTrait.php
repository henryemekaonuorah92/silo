<?php

namespace Silo\Base;

use Doctrine\ORM\EntityManager;

trait EntityManagerAwareTrait
{
    /**
     * @var EntityManager
     */
    protected $em;

    /**
     * Sets the container.
     *
     * @param EntityManager|null $em An EntityManager instance or null
     */
    public function setEntityManager(EntityManager $em = null)
    {
        $this->em = $em;
    }
}
