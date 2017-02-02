<?php

namespace Silo\Inventory;

use Silex\Application;
use Silex\Api\ControllerProviderInterface;
use Silo\Inventory\Model\Batch;
use Silo\Inventory\Model\BatchCollection;
use Silo\Inventory\Model\Context;
use Silo\Inventory\Model\Location;
use Silo\Inventory\Model\Operation;
use Silo\Inventory\Repository\Modifier;
use Silo\Inventory\Validator\Constraints\LocationExists;
use Silo\Inventory\Validator\Constraints\SkuExists;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\Constraints as Constraint;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class OperationController implements ControllerProviderInterface
{
    public function connect(Application $app)
    {
        $controllers = $app['controllers_factory'];

        /*
         * Fetch all modified Location
         */
        $controllers->get('/batches', function (Application $app, Request $request) {
            $query = $app['em']->createQueryBuilder();
            $query->select('location, batch, product')
                ->from('Inventory:Location', 'location')
                ->innerJoin('location.batches', 'batch')
                ->innerJoin('batch.product', 'product')
                ->andWhere('batch.quantity != 0');

            if ($since = $request->get('since')) {
                $from = new \DateTime($since);
                /** @var \Doctrine\ORM\QueryBuilder $modifiedQuery */
                $modifiedQuery = $app['em']->createQueryBuilder();
                $modifiedQuery->select('op, source, target')
                    ->from('Inventory:Operation', 'op')
                    ->leftJoin('op.source', 'source')
                    ->leftJoin('op.target', 'target')
                    ->andWhere($modifiedQuery->expr()->isNotNull('op.doneAt'))
                    ->andWhere('op.requestedAt >= :created')
                    ->setParameter('created', $from->format('Y-m-d H:i:s'));

                $modifiedLocations = [];
                foreach ($modifiedQuery->getQuery()->getResult() as $op /** @var Operation $op*/) {
                    if ($target = $op->getTarget()) {
                        $modifiedLocations[$target->getCode()] = 1;
                    }
                    if ($source = $op->getSource()) {
                        $modifiedLocations[$source->getCode()] = 1;
                    }
                }

                $codes = array_keys($modifiedLocations);
                if (empty($codes)) {
                    return new JsonResponse([
                        'since' => $since,
                        'locations' => []
                    ]);
                }

                $query->andWhere($modifiedQuery->expr()->in('location.code', $codes));
            }

            $result = $query->getQuery()->execute();

            return new JsonResponse([
                'since' => $since,
                'locations' => array_map(function (Location $l) {
                    return [
                        'code' => $l->getCode(),
                        'batches' => array_map(function (Batch $b) {
                            return [
                                'sku' => $b->getProduct()->getSku(),
                                'quantity' => $b->getQuantity()
                            ];
                        }, $l->getBatches()->toArray())
                    ];
                }, $result)
            ]);
        });

        /*
         * Create operations massively by uploading a CSV file
         */
        $controllers->post('/import', function () use ($app) {
            $csv = new \parseCSV($_FILES['file']['tmp_name']);
            $operationMap = [];
            foreach ($csv->data as $line) {
                ++$line;
                /** @var ConstraintViolationList $violations */
                $violations = $app['validator']->validate($line, [
                    new Constraint\Collection([
                        'source' => [new Constraint\NotBlank(), new LocationExists()],
                        'target' => [new Constraint\NotBlank(), new LocationExists()],
                        'sku' => [new Constraint\Required(), new SkuExists()],
                        'quantity' => new Constraint\Range(['min' => -100, 'max' => 100]),
                    ]),
                    new Constraint\Callback(function($payload, ExecutionContextInterface $context)
                    {
                        if ($payload['source'] === "VOID" && $payload['target'] === "VOID") {
                            $context->buildViolation('Source and target cannot be both VOID')
                                ->addViolation();
                        }
                    })
                ]);

                if ($violations->count() > 0) {
                    return new JsonResponse(['errors' => array_map(function ($violation) {
                        return (string) $violation;
                    }, iterator_to_array($violations->getIterator()))]);
                }

                $product = $app['em']->getRepository('Inventory:Product')->findOneBy(['sku' => $line['sku']]);
                $batch = new Batch($product, $line['quantity']);

                $key = $line['source'].','.$line['target'];
                if (!isset($operationMap[$key])) {
                    $operationMap[$key] = new BatchCollection();
                }

                $operationMap[$key]->addBatch($batch);
            }

            // Build now the corresponding operations
            foreach ($operationMap as $operation => $batches) {
                list($sourceCode, $targetCode) = explode(',', $operation);
                if ($sourceCode == 'VOID') {$sourceCode = null;}
                if ($targetCode == 'VOID') {$targetCode = null;}


                $source = !empty($sourceCode) ?
                    $app['em']->getRepository('Inventory:Location')->findOneBy(['code' => $sourceCode]) :
                    null;
                $target = !empty($targetCode) ?
                    $app['em']->getRepository('Inventory:Location')->findOneBy(['code' => $targetCode]) :
                    null;

                $operation = new Operation($app['current_user'], $source, $target, $batches);

                $type = $app['em']->getRepository('Inventory:OperationType')->getByName('mass upload');
                $operation->setType($type);

                $app['em']->persist($operation);
                $app['em']->flush();
                $operation->execute($app['current_user']);
                $app['em']->flush();
            }

            return new JsonResponse([]);
        });

        /*
         * Inspect Operations
         */
        $controllers->get('/', function (Application $app) {
            $query = $app['em']->createQueryBuilder();
            $query->select('operation, source, target, type, context, location, contextType')
                ->from('Inventory:Operation', 'operation')
                ->leftJoin('operation.source', 'source')
                ->leftJoin('operation.target', 'target')
                ->leftJoin('operation.location', 'location')
                ->leftJoin('operation.operationType', 'type')
                ->leftJoin('operation.contexts', 'context')
                ->leftJoin('context.type', 'contextType')
                ->orderBy('operation.id', 'DESC')
                ->setMaxResults(1000)
                ;

            $result = $query->getQuery()->execute();

            return new JsonResponse(
                array_map(function (Operation $op) {
                    return [
                        'id' => $op->getId(),
                        'source' => $op->getSource() ? $op->getSource()->getCode() : null,
                        'target' => $op->getTarget() ? $op->getTarget()->getCode() : null,
                        'type' => $op->getType(),
                        'status' => $op->getStatus()->toArray(),
                        'location' => $op->getLocation() ? $op->getLocation()->getCode() : null,
                        'contexts' => array_map(function(Context $context){
                            return [
                                'name' => $context->getName(),
                                'value' => $context->getValue()
                            ];
                        }, $op->getContexts()->toArray())
                    ];
                }, $result)
            );
        });

        $controllers->get('/{id}', function ($id, Application $app) {
            $operations = $app['em']->getRepository('Inventory:Operation');
            /** @var Operation $operation */
            $op = $operations->find($id);

            if (!$op) {
                throw new \Exception("Operation $id does not exist");
            }

            return new JsonResponse([
                'id' => (int) $id,
                'batches' => array_map(function (Batch $b) {
                    return [
                        'product' => $b->getProduct()->getSku(),
                        'quantity' => $b->getQuantity(),

                    ];
                }, $op->getBatches()->toArray()),
                'location' => $op->getLocation() ? $op->getLocation()->getCode() : null,
                'source' => $op->getSource() ? $op->getSource()->getCode() : null,
                'target' => $op->getTarget() ? $op->getTarget()->getCode() : null,
                'type' => $op->getType(),
                'status' => $op->getStatus()->toArray(),
                'rollback' => $op->getRollbackOperation() ? $op->getRollbackOperation()->getId() : null,
                'contexts' => array_map(function(Context $context){
                    return [
                        'name' => $context->getName(),
                        'value' => $context->getValue()
                    ];
                }, $op->getContexts()->toArray())
            ]);
        });

        $controllers->post('/{id}/{action}', function ($id, $action, Application $app) {
            $operations = $app['em']->getRepository('Inventory:Operation');
            /** @var Operation $op */
            $op = $operations->find($id);

            if (!$op) {
                throw new \Exception("Operation $id does not exist");
            }

            $user = $app['current_user'];

            switch ($action) {
                case 'rollback':
                    $rollbackOp = $op->createRollback($user);
                    $app['em']->persist($rollbackOp);
                    $app['em']->flush();
                    $rollbackOp->execute($user);
                    break;
                case 'execute':
                    $op->execute($user);
                    break;
                case 'cancel':
                    $op->cancel($user);
                    break;
            }

            $app['em']->flush();

            return new JsonResponse([], 201);
        })->assert('action', 'rollback|cancel|execute');;

        $controllers->post('/{id}/execute', function ($id, Application $app) {
            $operations = $app['em']->getRepository('Inventory:Operation');
            /** @var Operation $op */
            $op = $operations->find($id);

            if (!$op) {
                throw new \Exception("Operation $id does not exist");
            }

            $rollbackOp = $op->createRollback($app['current_user']);
            $app['em']->persist($rollbackOp);
            $app['em']->flush();

            $rollbackOp->execute($app['current_user']);
            $app['em']->flush();

            return new JsonResponse([], 201);
        });

        return $controllers;
    }
}
