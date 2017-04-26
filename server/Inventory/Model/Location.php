<?php

namespace Silo\Inventory\Model;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Silo\Inventory\Collection\BatchCollection;
use Silo\Inventory\Collection\ModifierCollection;

/**
 * @ORM\Entity(repositoryClass="Silo\Inventory\Repository\LocationRepository")
 * @ORM\Table(name="location", uniqueConstraints={
 *     @ORM\UniqueConstraint(name="location_idx", columns={"code"})
 * })
 */
class Location
{
    const CODE_ROOT = 'root';

    /**
     * @var int
     *
     * @ORM\Column(name="location_id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var string
     * @ORM\Column(name="code", type="string", length=32, nullable=true)
     */
    private $code;

    /**
     * @ORM\ManyToOne(targetEntity="Location", inversedBy="children")
     * @ORM\JoinColumn(name="parent", referencedColumnName="location_id")
     */
    private $parent;

    /**
     * @ORM\OneToMany(targetEntity="Location", mappedBy="parent")
     */
    private $children;

    /**
     * One Operation has many Batches.
     *
     * @ORM\OneToMany(targetEntity="Batch", mappedBy="location", cascade={"persist"})
     */
    private $batches;

    /**
     * @var ArrayCollection|Modifier[] One Operation has many Modifiers.
     * @ORM\OneToMany(targetEntity="Modifier", mappedBy="location")
     */
    private $modifiers;

    /**
     * @var boolean
     * @ORM\Column(name="isDeleted", type="boolean")
     */
    private $isDeleted = false;

    /**
     * @param $code
     *
     * @todo Code should be constrained by a regex
     */
    public function __construct($code)
    {
        $regex = '/^[\w\d-_]+$/';
        if (!preg_match($regex, $code)) {
            throw new \LogicException("Location name should follow $regex, got $code");
        }

        $this->code = $code;
        $this->batches = new ArrayCollection();
        $this->modifiers = new ModifierCollection();
        $this->children = new ArrayCollection();
    }

    /**
     * @return BatchCollection Deep copy of the contained Batches
     */
    public function getBatches()
    {
        return BatchCollection::fromCollection($this->batches)->copy();
    }

    /**
     * An Operation is applied onto its source and target, or its content,
     * depending if it is an Operation moving Batches or an Operation moving a Location.
     *
     * We delegate applying the Operation to Location itself, in order to keep $batches
     * our of reach of the developers. We want them indeed to be immutable, but not necessarly
     * for Location Batches for database size reasons.
     *
     * @todo Tests show that this creates duplicate Batches, please investigate
     *
     * @param Operation $operation
     */
    public function apply(Operation $operation)
    {
        if ($this->isDeleted) {
            throw new \LogicException("Cannt apply an Operation to a deleted Location");
        }

        $that = $this;
        if (self::compare($operation->getLocation(), $this)) { // $this is the moved Location
            $currentParent = $this->getParent();
            if (!self::compare($currentParent, $operation->getSource())) {
                throw new \LogicException("$this cannot be applied $operation has it is no longer in ".$this->parent);
            }
            if (!is_null($operation->getTarget()) && self::compare($this, $operation->getTarget()->getParent())) {
                throw new \LogicException("$this cannot be applied $operation has it would create a cycle");
            }
            $this->parent = $operation->getTarget();

            // Deal with deletion and creation cases
            if (is_null($operation->getSource())) { // Creation
                $this->isDeleted = false;
            } else if (is_null($operation->getTarget())) {  // Deletion
                $this->isDeleted = true;
            }
            $this->parent = $operation->getTarget();
        } else if (self::compare($operation->getSource(), $this)) {
            // $this is the source Location, we substract the Operation Batches
            $this->batches = BatchCollection::fromCollection($this->batches);
            $this->batches->merge($operation->getBatches()->deduplicateProducts()->opposite());
        } else if (self::compare($operation->getTarget(), $this)) {
            // $this is the target Location, we add the Operation Batches
            $this->batches = BatchCollection::fromCollection($this->batches);
            $this->batches->merge($operation->getBatches()->deduplicateProducts()->copy());
        } else {
            throw new \LogicException("$operation cannot be applied on unrelated $this");
        }
        // Update owning side of the relationship
        if ($this->batches) {
            $ref = $this->batches->toArray();
            array_walk($ref, function (Batch $batch) use ($that) {
                $batch->setLocation($that);
            });
        }
    }

    /**
     * Compares two Locations together by code.
     *
     * @todo Maybe overkill if comparison per id does the job
     *
     * @param self|null $a
     * @param self|null $b
     *
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

        return $a->id == $b->id;
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

    /**
     * @return string
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     * @return ModifierCollection
     */
    public function getModifiers()
    {
        return new ModifierCollection($this->modifiers->toArray());
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
     * @return mixed
     */
    public function getChildren()
    {
        return $this->children->toArray();
    }

    /**
     * @return bool
     */
    public function isDeleted()
    {
        return $this->isDeleted;
    }
}
