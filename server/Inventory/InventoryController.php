<?php

namespace Silo\Inventory;

use Silex\Application;
use Silex\Api\ControllerProviderInterface;
use Silo\Inventory\Model\Batch;
use Silo\Inventory\Model\BatchCollection;
use Silo\Inventory\Model\Context;
use Silo\Inventory\Model\Location;
use Silo\Inventory\Model\Operation;
use Silo\Inventory\Validator\Constraints\LocationExists;
use Silo\Inventory\Validator\Constraints\SkuExists;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\Constraints as Constraint;
use Symfony\Component\Validator\ConstraintViolationList;

/**
 * Endpoints.
 */
class InventoryController implements ControllerProviderInterface
{
    public function connect(Application $app)
    {
        $controllers = $app['controllers_factory'];

        /*
         * Inspect a Location given its code
         */
        $controllers->get('/location/{code}', function ($code, Application $app) {
            $locations = $app['em']->getRepository('Inventory:Location');
            /** @var Location $location */
            $location = $locations->forceFindOneByCode($code);

            $parent = $location->getParent();

            return new JsonResponse([
                'code' => $code,
                'parent' => $parent ? $parent->getCode() : null,
                'childs' => array_map(function (Location $l) {
                    return $l->getCode();
                }, $locations->findByParent($location)),
            ]);
        });

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
         * Inspect a Location given its code
         */
        $controllers->get('/location/{code}/batches', function ($code, Application $app) {
            $query = $app['em']->createQueryBuilder();
            $query->select('location, batch, product')
                ->from('Inventory:Location', 'location')
                ->innerJoin('location.batches', 'batch')
                ->innerJoin('batch.product', 'product')
                ->andWhere('location.code = :code')
                ->andWhere('batch.quantity != 0')
                ->setParameter('code', $code);

            $result = $query->getQuery()->execute();
            if (empty($result)) {
                return new JsonResponse([]);
            }

            return new JsonResponse(
                array_map(function (Batch $b) {
                    return [
                        'product' => $b->getProduct()->getSku(),
                        'quantity' => $b->getQuantity(),
                    ];
                }, $result[0]->getBatches()->toArray())
            );
        });

        /*
         * Create operations massively by uploading a CSV file
         */
        $controllers->post('/operation/import', function () use ($app) {
            $csv = new \parseCSV($_FILES['file']['tmp_name']);
            $operationMap = [];
            $locations = $app['em']->getRepository('Inventory:Location');
            foreach ($csv->data as $line) {
                ++$line;
                /** @var ConstraintViolationList $violations */
                $violations = $app['validator']->validate($line, [
                    new Constraint\Collection([
                        'source' => [new LocationExists()],
                        'target' => [new LocationExists()],
                        'sku' => [new Constraint\Required(), new SkuExists()],
                        'quantity' => new Constraint\Range(['min' => -100, 'max' => 100]),
                    ]),
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

                $source = !empty($sourceCode) ?
                    $app['em']->getRepository('Inventory:Location')->findOneBy(['code' => $sourceCode]) :
                    null;
                $target = !empty($targetCode) ?
                    $app['em']->getRepository('Inventory:Location')->findOneBy(['code' => $targetCode]) :
                    null;

                $operation = new Operation($app['current_user'], $source, $target, $batches);

                $type = $app['em']->getRepository('Inventory:OperationType')->getByName('mass creation');
                $operation->setType($type);

                $app['em']->persist($operation);
                $app['em']->flush();
                $operation->execute($app['current_user']);
                $app['em']->flush();
            }

            return new JsonResponse([]);
        });

        /*
         * Edit Batches in a given Location
         */
        $controllers->post('/location/{code}/batches', function ($code, Application $app, Request $request) {
            $locations = $app['em']->getRepository('Inventory:Location');
            /** @var Location $location */
            $location = $locations->forceFindOneByCode($code);
            $csv = new \parseCSV();
            $csv->offset = 1;
            $csv->keep_file_data = true;
            $csv->parse($_FILES['file']['tmp_name']);

            // Check first line of file
            $shouldStartWith = $request->get('merge') === 'true' ? 'merge' : 'replace';
            if (substr($csv->file_data, 0, strlen($shouldStartWith)) !== $shouldStartWith) {
                return new JsonResponse(['errors' => ["File should start with \"$shouldStartWith\""]]);
            }

            // Create a BatchCollection out of a CSV file
            $batches = new BatchCollection();
            foreach ($csv->data as $line) {
                ++$line;
                /** @var ConstraintViolationList $violations */
                $violations = $app['validator']->validate($line, [
                    new Constraint\Collection([
                        'sku' => [new Constraint\Required(), new SkuExists()],
                        'quantity' => new Constraint\Range(['min' => -100, 'max' => 100]),
                    ]),
                ]);

                if ($violations->count() > 0) {
                    return new JsonResponse(['errors' => array_map(function ($violation) {
                        return (string) $violation;
                    }, iterator_to_array($violations->getIterator()))]);
                }

                $product = $app['em']->getRepository('Inventory:Product')->findOneBy(['sku' => $line['sku']]);
                $batch = new Batch($product, $line['quantity']);

                $batches->addBatch($batch);
            }

            switch ($request->get('merge')) {
                // Merge the uploaded batch into the location
                case 'true':
                    $typeName = 'batch merge';
                    $operation = new Operation($app['current_user'], null, $location, $batches);
                    break;
                // the Location batches are replaced by the uploaded ones
                // We achieve this by computing the difference and applying it with an operation
                // SOURCE + OP = TARGET
                // hence OP = TARGET - SOURCE
                case 'false':
                    $typeName = 'batch replace';
                    $diffBatches = $batches->diff($location->getBatches());
                    $operation = new Operation($app['current_user'], null, $location, $diffBatches);
                    break;
                default:
                    throw new \Exception('merge parameter should be present and be either true or false');
            }

            $type = $app['em']->getRepository('Inventory:OperationType')->getByName($typeName);
            $operation->setType($type);

            $app['em']->persist($operation);
            $app['em']->flush();
            $operation->execute($app['current_user']);
            $app['em']->flush();

            // Is it merge or adjust ?
            return new Response('', Response::HTTP_ACCEPTED);
        });

        /*
         * Inspect Operations
         */
        $controllers->get('/operation', function (Application $app) {
            $query = $app['em']->createQueryBuilder();
            $query->select('operation, source, target, type, context, contextType')
                ->from('Inventory:Operation', 'operation')
                ->leftJoin('operation.source', 'source')
                ->leftJoin('operation.target', 'target')
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

        $controllers->get('/operation/{id}', function ($id, Application $app) {
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
                'rollback' => $op->getRollbackOperation() ? $op->getRollbackOperation()->getId() : null
            ]);
        });

        $controllers->post('/operation/{id}/rollback', function ($id, Application $app) {
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
