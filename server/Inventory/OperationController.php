<?php

namespace Silo\Inventory;

use Doctrine\ORM\QueryBuilder;
use Silex\Application;
use Silex\Api\ControllerProviderInterface;
use Silo\Base\JsonRequest;
use Silo\Inventory\Finder\OperationFinder;
use Silo\Inventory\Model\Batch;
use Silo\Inventory\Collection\BatchCollection;
use Silo\Inventory\Model\Context;
use Silo\Inventory\Model\Location;
use Silo\Inventory\Model\Operation;
use Silo\Inventory\Model\OperationSet;
use Silo\Inventory\Model\OperationType;
use Silo\Inventory\Repository\ModifierRepository;
use Silo\Inventory\Validator\Constraints\LocationExists;
use Silo\Inventory\Validator\Constraints\SkuExists;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Validator\Constraints as Constraint;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class OperationController implements ControllerProviderInterface
{
    public function connect(Application $app)
    {
        $controllers = $app['controllers_factory'];
        $operations = $app['em']->getRepository('Inventory:Operation');
        $operationProvider = function ($id) use ($operations) {
            $operation = $operations->find($id);
            if (!$operation) {
                throw new NotFoundHttpException("Operation $id cannot be found");
            }
            return $operation;
        };

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
                        'batches' => $l->getBatches()->toRawArray()
                    ];
                }, $result)
            ]);
        });

        /*
         * Create operations massively by uploading a CSV file
         */
        $controllers->post('/import', function (Request $request) use ($app) {
            /** @var \Symfony\Component\HttpFoundation\File\UploadedFile $file */
            $file = $request->files->get('file');
            $csv = new \parseCSV($file->getPathname()); // ['tmp_name']
            $operationMap = [];
            foreach ($csv->data as $line) {
                ++$line;
                /** @var ConstraintViolationList $violations */
                $violations = $app['validator']->validate($line, [
                    new Constraint\Collection([
                        'source' => [new Constraint\NotBlank(), new LocationExists()],
                        'target' => [new Constraint\NotBlank(), new LocationExists()],
                        'sku' => [new Constraint\Required(), new SkuExists()],
                        'quantity' => new Constraint\Required(), //new Constraint\Range(['min' => -100, 'max' => 100]),
                    ]),
                    new Constraint\Callback(function ($payload, ExecutionContextInterface $context) {
                        if (isset($payload['source']) &&
                            isset($payload['target']) &&
                            $payload['source'] === "VOID" &&
                            $payload['target'] === "VOID") {
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
                if (!$product && $app['productProvider']) {
                    $product = $app['productProvider']->getProduct($line['sku']);
                }
                $batch = new Batch($product, $line['quantity']);

                $key = $line['source'].','.$line['target'];
                if (!isset($operationMap[$key])) {
                    $operationMap[$key] = new BatchCollection();
                }

                $operationMap[$key]->addBatch($batch);
            }

            // New operation set
            $set = new OperationSet(null, ['description' => $request->request->get('description')]);

            // Build now the corresponding operations
            $type = $app['em']->getRepository('Inventory:OperationType')->getByName('mass upload');

            foreach ($operationMap as $operation => $batches) {
                list($sourceCode, $targetCode) = explode(',', $operation);
                if ($sourceCode == 'VOID') {
                    $sourceCode = null;
                }
                if ($targetCode == 'VOID') {
                    $targetCode = null;
                }

                $source = !empty($sourceCode) ?
                    $app['em']->getRepository('Inventory:Location')->findOneBy(['code' => $sourceCode]) :
                    null;
                $target = !empty($targetCode) ?
                    $app['em']->getRepository('Inventory:Location')->findOneBy(['code' => $targetCode]) :
                    null;

                $operation = new Operation($app['current_user'], $source, $target, $batches);
                $operation->setType($type);
                $operation->execute($app['current_user']);
                $app['em']->persist($operation);
                $set->add($operation);
            }

            if (!$set->isEmpty()) {
                $app['em']->persist($set);
                $app['em']->flush();
            }

            return new JsonResponse([]);
        });

        $controllers->get('/types', function () use ($app) {
            $types = $app['em']->getRepository('Inventory:OperationType')->findAll();
            return new JsonResponse(array_map(function (OperationType $type) {
                return $type->getName();
            }, $types));
        });

        /*
         * Inspect Operations
         */
        // @todo test This
        $controllers->post('/search', function (Request $request) use ($app) {

            $forceBatches = (bool) $request->get('forceBatches', 0);
            $withBatches = false;
            $maxResults = $request->get('limit', 100);

            /** @var QueryBuilder $query */
            $query = $app['em']->createQueryBuilder();
            $query->select('operation, source, target, type, context, location,doneBy,requestedBy,cancelledBy')
                ->from('Inventory:Operation', 'operation')
                ->leftJoin('operation.source', 'source')
                ->leftJoin('operation.doneBy', 'doneBy')
                ->leftJoin('operation.cancelledBy', 'cancelledBy')
                ->leftJoin('operation.requestedBy', 'requestedBy')
                ->leftJoin('operation.target', 'target')
                ->leftJoin('operation.location', 'location')
                ->leftJoin('operation.operationType', 'type')
                ->leftJoin('operation.operationSets', 'context')
                ->orderBy('operation.id', 'DESC')
                ;

            if (is_numeric($maxResults) && $maxResults > 0) {
                $query->setMaxResults($maxResults);
            }

            if ($location = $request->get('location')) {
                $query->andWhere($query->expr()->orX(
                    'source.code = :location',
                    'target.code = :location',
                    'location.code = :location'
                ));
                $query->setParameter('location', $location);
            }

            $filters = $request->request->get('filters');
            if (is_array($filters)) {
                for ($i=0;$i<count($filters);$i++) {
                    $filter = $filters[$i];
                    $type = $filter['type'];
                    $value = $filter['value'];
                    $var = $type.$i; // a query parameter unique name
                    switch ($type) {
                        case 'target':
                        case 'source':
                            $query->andWhere($query->expr()->in(
                                'source.code',
                                ':'.$var
                            ));
                            $query->setParameter($var, $value);
                            break;

                        case 'location':
                            $query->andWhere($query->expr()->orX(
                                'source.code = :'.$var,
                                'target.code = :'.$var,
                                'location.code = :'.$var
                            ));
                            $query->setParameter($var, $value);
                            break;

                        // Luckily, available "By" are the right type already
                        case 'doneBy':
                        case 'cancelledBy':
                        case 'requestedBy':
                            $query->andWhere($query->expr()->in(
                                $type.'.name',
                                ':'.$var
                            ));
                            $query->setParameter($var, $value);
                            break;

                        case 'doneAt':
                        case 'cancelledAt':
                        case 'requestedAt':
                            $startDate = new \DateTime($value['startDate']);
                            $endDate = new \DateTime($value['endDate']);
                            $query->andWhere($query->expr()->between(
                                'operation.'.$type,
                                ':start'.$var,
                                ':end'.$var
                            ));
                            $query->setParameter(':start'.$var, $startDate->setTime(0, 0, 0)->format('Y-m-d H:i:s'));
                            $query->setParameter(':end'.$var, $endDate->setTime(23, 59, 59)->format('Y-m-d H:i:s'));
                            break;

                        case 'type':
                            $query->andWhere($query->expr()->in(
                                'type.name',
                                ':'.$var
                            ));
                            $query->setParameter($var, $value);
                            break;

                        case 'status':
                            switch ($value) {
                                case 'cancelled':
                                    $query->andWhere($query->expr()->isNotNull('operation.cancelledAt'));
                                    break;
                                case 'pending':
                                    $query->andWhere($query->expr()->isNull('operation.cancelledAt'));
                                    $query->andWhere($query->expr()->isNull('operation.doneAt'));
                                    break;
                                case 'done':
                                    $query->andWhere($query->expr()->isNotNull('operation.doneAt'));
                                    break;
                            }
                            break;

                        case 'sku':
                            $withBatches = true;
                            $query->addSelect('batches,product')
                                ->innerJoin('operation.batches', 'batches')
                                ->innerJoin('batches.product', 'product')
                                ->andWhere($query->expr()->in(
                                    'product.sku',
                                    ':'.$var
                                ));
                            $query->setParameter($var, $value);
                            break;
                    }
                }
            }

            if ($forceBatches && !$withBatches) {
                $query->addSelect('batches,product')
                    ->innerJoin('operation.batches', 'batches')
                    ->innerJoin('batches.product', 'product');
                $withBatches = true;
            }

            $result = $query->getQuery()->execute();

            return new JsonResponse(
                array_map(function (Operation $op) use ($withBatches) {
                    $data = [
                        'id' => $op->getId(),
                        'source' => $op->getSource() ? $op->getSource()->getCode() : null,
                        'target' => $op->getTarget() ? $op->getTarget()->getCode() : null,
                        'type' => $op->getType(),
                        'status' => $op->getStatus()->toArray(),

                        'location' => $op->getLocation() ? $op->getLocation()->getCode() : null,
                        'contexts' => array_map(function (OperationSet $context) {
                            return [
                                'id' => $context->getId(),
                                'value' => $context->getValue()
                            ];
                        }, $op->getOperationSets())
                    ];
                    if ($withBatches) {
                        $data['batches'] = $op->getBatches()->toRawArray();
                    }
                    return $data;
                }, $result)
            );
        })->before(new JsonRequest());

        $controllers->get('/{id}', function ($id, Application $app) {
            $operations = $app['em']->getRepository('Inventory:Operation');
            /** @var Operation $operation */
            $op = $operations->find($id);

            if (!$op) {
                throw new \Exception("Operation $id does not exist");
            }

            return new JsonResponse([
                'id' => (int) $id,
                'batches' => $op->getBatches()->toRawArray(),
                'location' => $op->getLocation() ? $op->getLocation()->getCode() : null,
                'source' => $op->getSource() ? $op->getSource()->getCode() : null,
                'target' => $op->getTarget() ? $op->getTarget()->getCode() : null,
                'type' => $op->getType(),
                'status' => $op->getStatus()->toArray(),
                'rollback' => $op->getRollbackOperation() ? $op->getRollbackOperation()->getId() : null,
                'contexts' => array_map(function (OperationSet $context) {
                    return [
                        'id' => $context->getId(),
                        'value' => $context->getValue()
                    ];
                }, $op->getOperationSets())
            ]);
        });

        $controllers->post('/{operation}/{action}', function (
            Operation $operation,
            $action,
            Request $request
        ) use ($app) {
            $user = $app['current_user'];
            switch ($action) {
                case 'rollback':
                    $type = $app['em']->getRepository('Inventory:OperationType')->getByName('rollback');

                    $description = $request->request->get('description');
                    if ($description === "") {
                        throw new \Exception("You should provide a small description");
                    }
                    $rollbackOp = $operation->createRollback($user);
                    $app['em']->persist($rollbackOp);
                    $rollbackOp->execute($user);
                    $rollbackOp->setType($type);

                    $set = new OperationSet($user, ['description' => $description]);
                    $set->add($rollbackOp);
                    $app['em']->persist($set);
                    break;
                case 'execute':
                    $override = null;
                    if (!empty($request->request->all())) {
                        $override = $app['BatchCollectionFactory']->makeFromArray($request->request);
                    }
                    $operation->execute($user, $override);
                    break;
                case 'cancel':
                    $operation->cancel($user);
                    break;
            }

            $app['em']->flush();

            return new JsonResponse([], 201);
        })
            ->assert('action', 'rollback|cancel|execute')
            ->before(new JsonRequest())
            ->convert('operation', $operationProvider)
        ;

        return $controllers;
    }
}
