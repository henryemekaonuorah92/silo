<?php

namespace Silo\Inventory\Model;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Silo\Inventory\Collection\BatchCollection;
use Silo\Inventory\Collection\ModifierCollection;

/**
 * @ORM\Entity(repositoryClass="Silo\Inventory\Repository\BatchSetRepository")
 * @ORM\Table(name="batch_set")
 */
class BatchSet
{
    /**
     * @var int
     *
     * @ORM\Column(name="batch_set_id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * One BatchSet has many Batches.
     *
     * @ORM\OneToMany(targetEntity="Batch", mappedBy="batchSet", cascade={"persist"})
     */
    private $batches;

    public function __construct()
    {
        $this->batches = new ArrayCollection();
    }

    /**
     * @return BatchCollection Deep copy of the contained Batches
     */
    public function getBatches()
    {
        return BatchCollection::fromCollection($this->batches)->copy();
    }

    /**
     * @return bool True if the location contain exclusively Product. Does not count children
     * and children's Products.
     * @todo rename to HasNoBatches
     */
    public function isEmpty()
    {
        foreach ($this->getBatches() as $batch) {
            if ($batch->getQuantity() > 0) {
                return false;
            }
        }

        return true;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }
}
