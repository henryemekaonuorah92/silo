<?php

namespace Silo\Base\Probe;

use Silo\Base\EntityManagerAwareTrait;

class NativeQueryProbe implements ProbeInterface
{
    use EntityManagerAwareTrait;

    /** @var string */
    private $query;

    /**
     * QueryProbe constructor.
     * @param string $query
     */
    public function __construct($query)
    {
        $this->query = $query;
    }

    /**
     * {@inheritdoc}
     */
    public function probe(ProbingWindow $window = null)
    {
        $stmt = $this->em->getConnection()->prepare($this->query);
        if (!$stmt->execute()) {
            return new Failure("Cannot query database");
        }

        $result = $stmt->fetch(\PDO::FETCH_COLUMN);
        if (!is_numeric($result)) {
            throw new ProbeException("Cannot evaluate query");
        }

        return $result;
    }
}
