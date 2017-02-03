<?php

namespace Silo\Inventory;
use Doctrine\ORM\EntityManager;

use Silo\Base\ValidationException;
use Symfony\Component\Validator\Constraints as Constraint;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Silo\Inventory\Model\BatchCollection;
use Silo\Inventory\Validator\Constraints\LocationExists;
use Silo\Inventory\Validator\Constraints\SkuExists;
use Silo\Inventory\Model\Batch;

/**
 * Build a BatchCollection out of an Array
 * Usefull in controllers
 */
class BatchCollectionFactory
{
    private $em;

    private $validator;

    public function __construct(EntityManager $em, ValidatorInterface $validator)
    {
        $this->em = $em;
        $this->validator = $validator;
    }

    public function makeFromArray($array)
    {
        $batches = new BatchCollection();

        foreach ($array as $line) {
            ++$line;
            /** @var ConstraintViolationList $violations */
            $violations = $this->validator->validate($line, [
                new Constraint\Collection([
                    'product' => [new Constraint\Required(), new SkuExists()],
                    // Negatives ?
                    'quantity' => new Constraint\Range(['min' => -100, 'max' => 100]),
                ]),
            ]);

            if ($violations->count() > 0) {
                throw new ValidationException($violations);
            }

            $product = $this->em->getRepository('Inventory:Product')->findOneBy(['sku' => $line['product']]);
            $batch = new Batch($product, $line['quantity']);

            $batches->addBatch($batch);
        }

        return $batches;
    }
}