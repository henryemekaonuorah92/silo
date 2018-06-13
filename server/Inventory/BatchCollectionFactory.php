<?php

namespace Silo\Inventory;

use Doctrine\ORM\EntityManager;
use Silo\Base\ValidationException;
use Symfony\Component\Validator\Constraints as Constraint;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Silo\Inventory\Collection\BatchCollection;
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

    // @todo implement an interface for this
    private $skuTransformer;

    private $productKey;

    public function __construct(
        EntityManager $em,
        ValidatorInterface $validator,
        $transformer = null,
        $productKey = 'productSku'
    ) {
        $this->em = $em;
        $this->validator = $validator;
        $this->skuTransformer = $transformer;
        $this->productKey = $productKey;
    }

    public function makeFromArray($array)
    {
        $batches = new BatchCollection();

        foreach ($array as $line) {
            ++$line;
            if ($this->skuTransformer && isset($line[$this->productKey])) {
                $line[$this->productKey] = $this->skuTransformer->transform($line[$this->productKey]);
            }
            /** @var ConstraintViolationList $violations */
            $violations = $this->validator->validate($line, [
                new Constraint\Collection([
                    'fields' => [
                        $this->productKey => [new Constraint\Required(), new SkuExists()],
                        // Negatives ?
                        'quantity' => new Constraint\Required(), //new Constraint\Range(['min' => -100, 'max' => 100]),
                    ],
                    'allowExtraFields' => true
                   ]),
            ]);

            if ($violations->count() > 0) {
                throw new ValidationException($violations);
            }

            $product = $this->em->getRepository('Inventory:Product')->findOneBy(['sku' => $line[$this->productKey]]);
            $batch = new Batch($product, $line['quantity']);

            $batches->addBatch($batch);
        }

        return $batches;
    }
}
