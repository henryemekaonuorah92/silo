<?php

namespace Silo\Inventory\Model;

/**
 * Gipsy wagon object that makes Operation status inspection easy.
 * Yes it is an anti pattern, but makes the interface of Operation more readable.
 */
class OperationStatus
{
    /**
     * @var User Who requested this Operation
     */
    private $requestedBy;

    /**
     * @var \DateTime When requested this Operation has been
     */
    private $requestedAt;

    /**
     * @var User Who did this Operation
     */
    private $doneBy;

    /**
     * @var \DateTime When requested this Operation has been
     */
    private $doneAt;

    /**
     * @var User Who cancelled this Operation
     */
    private $cancelledBy;

    /**
     * @var \DateTime When requested this Operation has been (Yoda style comment)
     */
    private $cancelledAt;

    private $isRollbacked;

    public function __construct(
        Operation $operation
    ) {
        // trick to access $operation privates
        $extract = \Closure::bind(
            function (Operation $operation) {
                return [
                    $operation->requestedBy,
                    $operation->requestedAt,
                    $operation->doneBy,
                    $operation->doneAt,
                    $operation->cancelledBy,
                    $operation->cancelledAt,
                    !is_null($operation->rollbackOperation),
                ];
            },
            null,
            Operation::class
        );

        list(
            $this->requestedBy,
            $this->requestedAt,
            $this->doneBy,
            $this->doneAt,
            $this->cancelledBy,
            $this->cancelledAt,
            $this->isRollbacked
        ) = $extract($operation);
    }

    public function toArray()
    {
        return [
            'requestedBy' => $this->requestedBy->getName(),
            'requestedAt' => $this->requestedAt->format('Y-m-d H:i:s'),
            'doneBy' => $this->doneBy ? $this->doneBy->getName() : null,
            'doneAt' => $this->doneAt ? $this->doneAt->format('Y-m-d H:i:s') : null,
            'cancelledBy' => $this->cancelledBy ? $this->cancelledBy->getName() : null,
            'cancelledAt' => $this->cancelledAt ? $this->cancelledAt->format('Y-m-d H:i:s') : null,
            'isRollbacked' => $this->isRollbacked,
            'isRollbackable' => !$this->isRollbacked && $this->doneAt
        ];
    }
}
