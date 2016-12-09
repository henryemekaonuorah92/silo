<?php

namespace Silo\Inventory\Model;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="location")
 */
class Location
{
    const CODE_ROOT = 'root';

    /**
     * @var int
     *
     * @ORM\Column(name="location_id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var string
     * @todo this shall be UNIQUE constrained
     * @ORM\Column(name="code", type="string", length=30, nullable=true)
     */
    private $code;

    /**
     * @ORM\OneToOne(targetEntity="Location")
     * @ORM\JoinColumn(name="parent", referencedColumnName="location_id")
     */
    private $parent;

    /**
     * One Operation has many Batches.
     *
     * @ORM\OneToMany(targetEntity="Batch", mappedBy="location_id", cascade={"persist"})
     */
    private $batches;

    /**
     * @param $code
     * @todo Code should be constrained by a regex
     */
    public function __construct($code)
    {
        $this->code = $code;
        $this->batches = new ArrayCollection();
    }

    /**
     * @return BatchCollection Copy of the contained Batches
     */
    public function getBatches()
    {
        return BatchCollection::fromCollection($this->batches)->copy();
    }

    /**
     * @param Batch $batch
     * @return bool True if $this contains $batch
     */
    public function contains(Batch $batch)
    {
        return $this->getBatches()->contains($batch);
    }

    /**
     * An Operation is applied onto its source and target, or its content,
     * depending if it is an Operation moving Batches or an Operation moving a Location
     *
     * We delegate applying the Operation to Location itself, in order to keep $batches
     * our of reach of the developers. We want them indeed to be immutable, but not necessarly
     * for Location Batches for database size reasons.
     *
     * @todo Tests show that this creates duplicate Batches, please investigate
     * @param Operation $operation
     */
    public function apply(Operation $operation)
    {
        if (self::compare($operation->getLocation(), $this)) { // $this is the moved Location
            if (!self::compare($this->parent, $operation->getSource())) {
                throw new \LogicException("$this cannot by $operation has it is no longer in ".$this->parent);
            }
            $this->parent = $operation->getTarget();
        } elseif (self::compare($operation->getSource(), $this)) {
            // $this is the source Location, we substract the Operation Batches from it
            $this->batches = BatchCollection::fromCollection($this->batches)
                ->decrementBy($operation->getBatches());
            $that = $this;
            $this->batches->forAll(function ($key, Batch $batch) use ($that) {
                $batch->setLocation($that);
            });
        } elseif (self::compare($operation->getTarget(), $this)) {
            // $this is the target Location, we add the Operation Batches
            $this->batches = BatchCollection::fromCollection($this->batches)
                ->incrementBy($operation->getBatches());

            $that = $this;
            $this->batches->forAll(function ($key, Batch $batch) use ($that) {
                $batch->setLocation($that);
            });
        } else {
            throw new \LogicException("$operation cannot be applied on unrelated $this");
        }
    }

    /**
     * Compares two Locations together by code
     *
     * @todo Maybe overkill if comparison per id does the job
     * @param self|null $a
     * @param self|null $b
     * @return bool True if $a is same as $b
     */
    public static function compare($a, $b)
    {
        if (!is_null($a) && !$a instanceof self) {
            throw new \InvalidArgumentException('$a should be a Location or null');
        }
        if (!is_null($b) && !$b instanceof self) {
            throw new \InvalidArgumentException('$b should be a Location or null');
        }
        if (is_null($a) && is_null($b)) {
            return true;
        }
        if (is_null($a) || is_null($b)) {
            return false;
        }

        return $a->code == $b->code;
    }

    /**
     * {@inheritdoc}
     */
    public function __toString()
    {
        return 'Location:'.$this->code;
    }

    /**
     * @return mixed
     */
    public function getParent()
    {
        return $this->parent;
    }
}
