<?php

namespace Silo\Inventory\Model;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * Represent a movement from a Location to another Location. You can either move a Location,
 * or a ProductQuantity set, but not both (could be possible, but let's make it simple).
 *
 * @ORM\Table(name="g_operation")
 * @ORM\Entity
 */
class Operation
{
    /**
     * @var integer
     *
     * @ORM\Column(name="operation_id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var User Who requested this Operation
     * @ORM\ManyToOne(targetEntity="User")
     * @ORM\JoinColumn(name="requested_by", referencedColumnName="user_id")
     */
    private $requestedBy;

    /**
     * @var \DateTime When requested this Operation has been
     * @ORM\Column(name="requested_at", type="datetimetz")
     */
    private $requestedAt;

    /**
     * @var User Who did this Operation
     * @ORM\ManyToOne(targetEntity="User")
     * @ORM\JoinColumn(name="done_by", referencedColumnName="user_id", nullable=true)
     */
    private $doneBy;

    /**
     * @var \DateTime When requested this Operation has been
     * @ORM\Column(name="done_at", type="datetimetz", nullable=true)
     */
    private $doneAt;

    /**
     * @var User Who cancelled this Operation
     * @ORM\ManyToOne(targetEntity="User")
     * @ORM\JoinColumn(name="cancelled_by", referencedColumnName="user_id", nullable=true)
     */
    private $cancelledBy;

    /**
     * @var \DateTime When requested this Operation has been
     * @ORM\Column(name="cancelled_at", type="datetimetz", nullable=true)
     */
    private $cancelledAt;

    /**
     * @var Location If null, this is a creation of something
     * @ORM\ManyToOne(targetEntity="Location")
     * @ORM\JoinColumn(name="source", referencedColumnName="location_id", nullable=true)
     */
    private $source;

    /**
     * @var Location If null, this is a removal of something
     * @ORM\ManyToOne(targetEntity="Location")
     * @ORM\JoinColumn(name="target", referencedColumnName="location_id", nullable=true)
     */
    private $target;

    /**
     * @var Location If set, this is a Location movement, or else this is a product movement
     * @ORM\ManyToOne(targetEntity="Location")
     * @ORM\JoinColumn(name="location", referencedColumnName="location_id", nullable=true)
     */
    private $location;

    /**
     * One Operation has many Batches.
     * @ORM\OneToMany(targetEntity="Batch", mappedBy="operation_id", cascade={"persist"})
     */
    private $batches;

    public function __construct(
        User $requestedBy,
        $source,
        $target,
        $content
    ){
        if (!$source instanceof Location && !is_null($source)) {
            throw new \LogicException("Source should be either Location or null");
        }
        if (!$target instanceof Location && !is_null($target)) {
            throw new \LogicException("Target should be either Location or null");
        }
        if (!$content instanceof Location && !$content instanceof ArrayCollection) {
            throw new \LogicException("Content should be either Location or ArrayCollection");
        }

        $this->requestedBy = $requestedBy;
        $this->source = $source;
        $this->target = $target;

        // @todo check ArrayCollection is not persisted yet
        $this->requestedAt = new \DateTime();

        if ($content instanceof Location){
            $this->location = $content;
        } else {
            $this->batches = $content;
            $that = $this;
            $this->batches->forAll(function($key, Batch $batch)use($that){
                $batch->setOperation($that);
            });
        }
    }

    public function execute(User $doneBy)
    {
        if ($location = $this->location){
            $this->location->apply($this);
        } else {
            if (!is_null($this->source)) {
                $this->source->apply($this);
            }
            if (!is_null($this->target)) {
                $this->target->apply($this);
            }
        }

        // @todo checks
        $this->doneBy = $doneBy;
        $this->doneAt = new \DateTime();
    }

    /**
     * @return Location
     */
    public function getSource()
    {
        return $this->source;
    }

    /**
     * @return Location
     */
    public function getTarget()
    {
        return $this->target;
    }

    /**
     * @return Location
     */
    public function getLocation()
    {
        return $this->location;
    }

    public function __toString()
    {
        return "Operation:".$this->id;
    }

    /**
     * @return BatchCollection Copy of the contained Batches
     */
    public function getBatches()
    {
        return BatchCollection::fromCollection($this->batches)->copy();
    }
}
