<?php

namespace Silo\Inventory\Model;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * Represent a movement of something from a Location to another Location.
 * Something can either be a Location, or a Batch set, but not both (could
 * be possible, but let's make it simple for futur generations).
 *
 * @ORM\Table(name="operation")
 * @ORM\Entity
 */
class Operation
{
    /**
     * @var int
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
     * @var \DateTime When requested this Operation has been (Yoda style comment)
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
     * @var ArrayCollection
     * @ORM\OneToMany(targetEntity="Batch", mappedBy="operation", cascade={"persist"})
     */
    private $batches;

    /**
     * @var OperationType Categorizes this operation
     * @ORM\ManyToOne(targetEntity="OperationType")
     * @ORM\JoinColumn(name="type", referencedColumnName="operation_type_id", nullable=true)
     */
    private $operationType;

    /**
     * @var Operation If present, then current operation has been rollbacked by rollback operation
     * @ORM\OneToOne(targetEntity="Operation")
     * @ORM\JoinColumn(name="rollback", referencedColumnName="operation_id", nullable=true)
     */
    private $rollbackOperation;

    /**
     * @param User $requestedBy
     * @param $source
     * @param $target
     * @param $content
     *
     * @todo check $content, if ArrayCollection, is not persisted yet, to prevent Batch reuse.
     */
    public function __construct(
        User $requestedBy,
        $source,
        $target,
        $content
    ) {
        if (!$source instanceof Location && !is_null($source)) {
            throw new \LogicException('Source should be either Location or null');
        }
        if (!$target instanceof Location && !is_null($target)) {
            throw new \LogicException('Target should be either Location or null');
        }
        if (is_null($source) && is_null($target)) {
            throw new \LogicException('A source or a target should at least be specified');
        }
        if (!$content instanceof Location && !$content instanceof ArrayCollection) {
            throw new \LogicException('Content should be either Location or ArrayCollection');
        }

        $this->requestedBy = $requestedBy;
        $this->source = $source;
        $this->target = $target;

        $this->requestedAt = new \DateTime();

        if ($content instanceof Location) {
            $this->location = $content;
        } else {
            $this->batches = $content;
            $that = $this;
            $ref = $this->batches->toArray();
            array_walk($ref, function (Batch $batch) use ($that) {
                $batch->setOperation($that);
            });
        }
    }

    /**
     * Perform $this and apply changes on related Locations. Will mark $this as
     * done after flush.
     *
     * @param User $doneBy
     */
    public function execute(User $doneBy)
    {
        if ($location = $this->location) {
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
     * @param User $rollbackUser
     *
     * @return Operation rollbacking operation. Execute it to make it happen.
     */
    public function createRollback(User $rollbackUser)
    {
        // not rollbacked by a done operation
        // has to be done to be rollbacked
        // rollbacking location op is not supported yet
        throw new \Exception('Not implemented yet');
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
        return 'Operation:'.$this->id;
    }

    /**
     * @return BatchCollection Copy of the contained Batches
     */
    public function getBatches()
    {
        return BatchCollection::fromCollection($this->batches)->copy();
    }

    /**
     * @param OperationType $type
     */
    public function setType(OperationType $type)
    {
        $this->operationType = $type;
    }

    /**
     * @return null|string Type of this operation.
     */
    public function getType()
    {
        if ($this->operationType) {
            return $this->operationType->getName();
        }

        return;
    }

    public function getStatus()
    {
        return new OperationStatus($this);
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return Operation
     */
    public function getRollbackOperation()
    {
        return $this->rollbackOperation;
    }
}
