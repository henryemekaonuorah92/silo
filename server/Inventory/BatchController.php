<?php

namespace Silo\Inventory;

use Silex\Application;
use Silo\Base\JsonRequest;
use Silo\Inventory\Model\Batch;
use Silo\Inventory\Model\Context;
use Silo\Inventory\Model\Location;
use Silo\Inventory\Model\Operation;
use Silo\Inventory\Model\OperationSet;
use Silex\Api\ControllerProviderInterface;
use Silo\Inventory\Finder\OperationFinder;
use Symfony\Component\HttpFoundation\Request;
use Silo\Inventory\Collection\BatchCollection;
use Symfony\Component\HttpFoundation\Response;
use Silo\Inventory\Repository\ModifierRepository;
use Silo\Inventory\Collection\OperationCollection;
use Symfony\Component\HttpFoundation\JsonResponse;
use Silo\Inventory\Validator\Constraints\SkuExists;
use Silo\Inventory\Validator\Constraints\LocationExists;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Constraints as Constraint;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class BatchController implements ControllerProviderInterface
{
    public function connect(Application $app)
    {
        $controllers = $app['controllers_factory'];

        /*
         * Create operations massively by uploading a CSV file
         *
         * Each Location will receive a mixed Operation targeting it
         * This way merge increments are seen as positive movements,
         * and decrements as negatives...
         */
        $controllers->post('/import', function (Request $request) use ($app) {
            $skuTransformer = $app['SkuTransformer'];

            /** @var \Symfony\Component\HttpFoundation\File\UploadedFile $file */
            $file = $request->files->get('file');
            $csv = new \parseCSV();
            $csv->offset = 1;
            $csv->keep_file_data = true;
            $csv->parse($file->getPathname());

            // Check first line of file
            $shouldStartWith = $request->get('type');
            if (substr($csv->file_data, 0, strlen($shouldStartWith)) !== $shouldStartWith) {
                return new JsonResponse(['errors' => ["File should start with \"$shouldStartWith\""]]);
            }

            /** @var BatchCollection[] $batchMap */
            // @todo Batchmap could be implemented in a class that allows optimization of Batch quantities. Ex:
            // MTLST -> VOID +2 SKUA
            // VOID -> MTLST +3 SKUB
            // would become VOID -> MTLST +3SKUB -2SKUA
            $batchMap = [];
            foreach ($csv->data as $line) {
                ++$line;
                /** @var ConstraintViolationList $violations */
                $violations = $app['validator']->validate($line, [
                    new Constraint\Collection([
                        'location' => [new Constraint\NotBlank(), new LocationExists()],
                        'product' => [new Constraint\Required(), new SkuExists()],
                        'quantity' => new Constraint\Required() //(['min' => -100, 'max' => 100]),
                    ]),
                    new Constraint\Callback(function ($payload, ExecutionContextInterface $context) {
                        if (isset($payload['location']) &&
                            $payload['location'] === "VOID"
                        ) {
                            $context->buildViolation('Location cannot be both VOID')
                                ->addViolation();
                        }
                    })
                ]);

                if ($violations->count() > 0) {
                    return new JsonResponse(['errors' => array_map(function ($violation) {
                        return (string)$violation;
                    }, iterator_to_array($violations->getIterator()))]);
                }

                if ($skuTransformer && isset($line['product'])) {
                    $line['product'] = $skuTransformer->transform($line['product']);
                }
                $product = $app['em']->getRepository('Inventory:Product')->findOneBy(['sku' => $line['product']]);

                $batch = new Batch($product, $line['quantity']);
                $key = $line['location'];
                if (!isset($batchMap[$key])) {
                    $batchMap[$key] = new BatchCollection([$batch]);
                } else {
                    $batchMap[$key]->addBatch($batch);
                }
            }

            // New operation set
            $set = new OperationSet(null, $request->request->get('description')? ['description' => $request->request->get('description')] : null);

            // Find type
            switch ($request->request->get('type')) {
                case 'merge':
                    $typeName = 'batch merge';
                    break;
                case 'replace':
                    $typeName = 'batch replace';
                    break;
                case 'superReplace':
                default:
                    throw new \Exception('Type is unknown');
            }
            $type = $app['em']->getRepository('Inventory:OperationType')->getByName($typeName);

            // Create needed Operations
            $pendingOperations = [];
            $pendingOperationAction = json_decode($request->get('pendingOperations'), true);
            $collateralOperationSets = [];
            foreach ($batchMap as $locationCode => $batches) {
                $location = $app['em']->getRepository('Inventory:Location')->forceFindOneByCode($locationCode);

                // First check for operation fixing
                $ignoredOps = new OperationCollection();
                foreach($pendingOperationAction as $opType => $data) {
                    $finder = new OperationFinder($app['em']);
                    $pendingOperationsInLocation = $finder->manipulating($location)
                        ->isPending()
                        ->isType($opType)
                        ->withBatches() // only operation that moves batches are taken into account
                        ->find();
                    if(count($pendingOperationsInLocation) && $data['action'] != 'ignore') {
                        // This is to create a context for operations that were pending but
                        // there was an action to be taken on them
                        $collateralOperationSets[$data['action']] = isset($collateralOperationSets[$data['action']]) ?
                            $collateralOperationSets[$data['action']] :
                            new OperationSet($app['current_user']);

                        foreach($pendingOperationsInLocation as $pendingOp) {
                            $collateralOperationSets[$data['action']]->add($pendingOp);
                        }
                        $app['em']->persist($collateralOperationSets[$data['action']]);
                    }

                    if(!in_array($data['action'], ['ignore', 'execute', 'cancel'])) {
                        throw new \Exception('Invalid action: '.$data['action']);
                    }
                    $ignoredOps->merge(new OperationCollection($pendingOperationsInLocation));

                    switch($data['action']) {
                        case 'execute':
                            foreach($pendingOperationsInLocation as $op) {
                                $op->execute($app['current_user']);                                
                            }
                            break;
                        case 'cancel':
                            foreach($pendingOperationsInLocation as $op) {
                                $op->cancel($app['current_user']);                                
                            }
                            break;
                    }
                }

                $finder = new OperationFinder($app['em']);
                $pendingOperationsInLocation = $finder->manipulating($location)
                    ->isPending()
                    ->withBatches() // only operation that moves batches are taken into account
                    ->find();
                $diffCollection = array_diff($pendingOperationsInLocation, $ignoredOps->toArray());

                if (count($diffCollection)) {
                    foreach($diffCollection as $operation) {
                        if(isset($pendingOperations[$operation->getType()]['qty'])) {
                            $pendingOperations[$operation->getType()]['qty'] += 1;
                        } else {
                            $pendingOperations[$operation->getType()]['qty'] = 1;
                        }
                    }
                    continue;
                }
                
                switch ($request->request->get('type')) {
                    case 'merge': // Merge the uploaded batch into the location
                        $operation = new Operation($app['current_user'], null, $location, $batches);
                        break;
                    // the Location batches are replaced by the uploaded ones
                    // We achieve this by computing the difference and applying it with an operation
                    // SOURCE + OP = TARGET
                    // hence OP = TARGET - SOURCE
                    case 'superReplace':
                        $diffBatches = $batches->diff($location->getBatches());
                        $operation = new Operation($app['current_user'], null, $location, $diffBatches);
                        break;
                    case 'replace':
                        $diffBatches = $batches->diff($location->getBatches()->intersectWith($batches));
                        $operation = new Operation($app['current_user'], null, $location, $diffBatches);
                        break;
                    default:
                        throw new \Exception('Type is unknown');
                }

                $operation->setType($type);
                $operation->execute($app['current_user']);
                $app['em']->persist($operation);
                $set->add($operation);
            }

            if(!empty($pendingOperations)) {
                return new JsonResponse(['errors' => ['pendingOperations' => $pendingOperations]], 400);
            }

            if (!$set->isEmpty()) {
                $app['em']->persist($set);
                $app['em']->flush();
                if(!empty($collateralOperationSets)) {
                    foreach($collateralOperationSets as $action => $cSet) {
                        $cSet->setValue(['description' => sprintf('Operations %sd along operation set %d', $action, $set->getId())]);
                        $app['em']->persist($cSet);
                        $app['em']->flush();
                    }
                }
            }


            return new JsonResponse([]);
        });

        return $controllers;
    }
}
