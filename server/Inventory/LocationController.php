<?php

namespace Silo\Inventory;

use Silex\Application;
use Silo\Base\JsonRequest;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\EntityManager;
use Silo\Inventory\Model\Batch;
use Silo\Inventory\Model\Location;
use Silo\Inventory\Model\Modifier;
use Silo\Inventory\Model\Operation;
use Silo\Inventory\Model\ModifierType;
use Silo\Inventory\Model\OperationSet;
use Silex\Api\ControllerProviderInterface;
use Silo\Inventory\Finder\OperationFinder;
use Symfony\Component\HttpFoundation\Request;
use Silo\Inventory\Collection\BatchCollection;
use Symfony\Component\HttpFoundation\Response;
use Silo\Inventory\Repository\LocationRepository;
use Silo\Inventory\Repository\ModifierRepository;
use Silo\Inventory\Collection\OperationCollection;
use Symfony\Component\HttpFoundation\JsonResponse;
use Silo\Inventory\Validator\Constraints\SkuExists;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Constraints as Constraint;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * Endpoints.
 *
 * @todo should factorize this a bit
 */
class LocationController implements ControllerProviderInterface
{
    public function connect(Application $app)
    {
        $controllers = $app['controllers_factory'];

        $controllers->get('/types', function () use ($app) {
            $types = $app['em']->getRepository('Inventory:ModifierType')->findAll();
            return new JsonResponse(array_map(function (ModifierType $type) {
                return $type->getName();
            }, $types));
        });

        $controllers->post('/search', function (Request $request) use ($app) {
            $code = $request->query->get('query');
            /** @var QueryBuilder $query */
            $query = $app['em']->createQueryBuilder();
            $query->select('Location.code')->from('Inventory:Location', 'Location')
                ->andWhere($query->expr()->like('Location.code', ':code'))
                ->setParameter('code', "%$code%");

            return new JsonResponse(array_map(
                function ($l) {
                    return $l['code'];
                },
                $query->getQuery()->getArrayResult()
            ), Response::HTTP_ACCEPTED);
        });

        /*
         * Inspect a Location given its code
         */
        $controllers->get('/{location}', function (Location $location, Application $app) {
            $parent = $location->getParent();

            /** @var QueryBuilder $query */
            $query = $app['em']->createQueryBuilder();
            $query->select('op, target, source, location')
                ->from('Inventory:Operation', 'op')
                ->leftJoin('op.target', 'target')
                ->leftJoin('op.source', 'source')
                ->leftJoin('op.location', 'location')
                ->andWhere($query->expr()->orX(
                    'op.target = :location',
                    'op.source = :location',
                    'op.location = :location'
                ))
                ->setParameter('location', $location)
                ->orderBy('op.requestedAt', 'DESC')
                ->setMaxResults(15)
                ;

            $operations = $query->getQuery()->getResult();

            return new JsonResponse([
                'isDeleted' => $location->isDeleted(),
                'code' => $location->getCode(),
                'parent' => $parent ? $parent->getCode() : null,
                'childs' => array_map(function (Location $l) {
                    return $l->getCode();
                }, $location->getChildren()),
                'operations' => array_map(function (Operation $op) {
                    return [
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
                }, $operations)
            ]);
        })->convert('location', $app['location.provider'](false));

        /*
         * Delete a Location
         */
        $controllers->delete('/{code}', function ($code, Application $app, Request $request) {
            $locations = $app['re'](Location::class);
            $location = $locations->forceFindOneByCode($code);
            $locations->delete($location, $app['current_user']);

            return new JsonResponse([], Response::HTTP_ACCEPTED);
        });

        $controllers->post('/{code}/respawn', function ($code, Application $app, Request $request) {
            $locations = $app['re'](Location::class);
            $location = $locations->forceFindOneByCode($code);
            $locations->respawn($location, $app['current_user']);

            return new JsonResponse([], Response::HTTP_ACCEPTED);
        });

        /*
         * Create an empty child Location
         */
        $controllers->post('/{code}/child', function ($code, Application $app, Request $request) {
            $locations = $app['em']->getRepository('Inventory:Location');
            /** @var Location $location */
            $parent = $locations->forceFindOneByCode($code);

            $location = new Location($request->get('name'));
            $app['em']->persist($location);
            $app['em']->flush();

            $operation = new Operation($app['current_user'], null, $parent, $location);
            $app['em']->persist($operation);
            $operation->execute($app['current_user']);
            $app['em']->flush();

            return new JsonResponse(null, Response::HTTP_ACCEPTED);
        });

        /*
         * Move Locations to a new parent
         */
        $controllers->patch('/{location}/child', function (Location $location, Request $request) use ($app) {
            $locations = $app['em']->getRepository('Inventory:Location');
            $type = $app['em']->getRepository('Inventory:OperationType')->getByName('parent assign');
            $operations = [];
            foreach ($request->request->all() as $childCode) {
                $child = $locations->forceFindOneByCode($childCode);

                // No need to move a child that is already at the right location
                if ($child->getParent() == $location) {
                    continue;
                }

                $op = new Operation($app['current_user'], $child->getParent(), $location, $child);
                $app['OperationValidator']->assertValid($op);
                $op->setType($type);
                array_push($operations, $op);

                $app['em']->persist($op);
            }
            $app['em']->flush();

            foreach ($operations as $op) {
                $op->execute($app['current_user']);
            }
            $app['em']->flush();

            return new JsonResponse([]);
        })
            ->convert('location', $app['location.provider']())
            ->before(new JsonRequest());

        /*
         * Inspect content of a Location
         */
        $controllers->get('/{code}/batches', function ($code) use ($app) {
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

            return new JsonResponse($result[0]->getBatches()->toRawArray());
        });

        /**
         * Get inclusive content from a Location
         * This method doesn't rely on Doctrine's object to speed up things
         * It could be speed up even more with some sort of partitionning strategy
         */
        $controllers->get('/{startCode}/inclusiveBatches', function ($startCode, Request $request) use ($app) {
            $modifier = $request->get('modifier');
            
            /** @var EntityManager $em */
            $em = $app['em'];

            // Build the adjacency tree first
            // We need all locations taht are not deleted
            $sql = <<<EOQ
            select location.code as code, parent.code as parentCode
            from silo_location location
            inner join silo_location parent on parent.location_id = location.parent
            where location.isDeleted = 0
            and parent.isDeleted = 0
            group by location.code
EOQ;
            $stmt = $em->getConnection()->prepare($sql);
            $stmt->execute();
            /** @var array $adjacencyMap [parent => childs */
            $adjacencyMap = [];
            while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
                $code = $row['code'];
                $parentCode = $row['parentCode'];
                isset($adjacencyMap[$parentCode]) ? $adjacencyMap[$parentCode][] = $code: $adjacencyMap[$parentCode] = [$code];
            }

            // Extract all batches from the inventory
            $join = $condition = "";
            if ($modifier) {
                $allowedModifiers = [
                    'store',
                    'damaged',
                    'surplus',
                    'picking'
                ];
                if (!in_array($modifier, $allowedModifiers)) {
                    throw new \Exception("Please use one modifier out of ".implode(
                        ',',
                            $allowedModifiers
                    ));
                }
                $join = <<<EOQ
                    INNER JOIN silo_modifier sm on sm.location = location.location_id
                    INNER JOIN silo_modifier_type smt on sm.modifier_type_id = smt.modifier_type_id
EOQ;
                $condition = sprintf(" AND smt.name = \"%s\" ", $modifier);
            }
            $sql = <<<EOQ
            select location.code as code, product.sku as s, product.name as n, SUM(batch.quantity) as q
            from silo_location location
            inner join silo_batch batch on location.location_id = batch.location_id
            inner join silo_product product on batch.product_id = product.product_id
            $join
            where batch.quantity != 0
            and location.isDeleted = 0
            $condition
            group by location.code, product.sku
EOQ;
            $stmt = $em->getConnection()->prepare($sql);
            $stmt->execute();
            /** @var array $productMap [code => [[sku, quantity], ]] */
            $productMap = [];
            $nameMap = [];
            while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
                $code = $row['code'];
                unset($row['code']);
                $nameMap[$row['s']] = $row['n'];
                isset($productMap[$code]) ? $productMap[$code][] = $row: $productMap[$code] = [$row];
            }


            // Accumulate all products from recursively walked locations, top to bottom
            // We'll use the accumulator later to build the final response
            $accumulator = [];
            $recursiveWalk = function ($code, $self) use ($adjacencyMap, $productMap, &$accumulator) {
                // Explore children first
                if (isset($adjacencyMap[$code])) {
                    foreach ($adjacencyMap[$code] as $child) {
                        call_user_func($self, $child, $self);
                    }
                }
                if (isset($productMap[$code])) {
                    array_push($accumulator, $productMap[$code]);
                }
            };
            $recursiveWalk($startCode, $recursiveWalk);

            // Lets' build the response
            $batches = [];
            foreach ($accumulator as $skuGroups) {
                foreach ($skuGroups as $skuGroup) {
                    $sku = $skuGroup['s'];
                    $quantity = $skuGroup['q'];
                    isset($batches[$sku]) ? $batches[$sku]+=$quantity: $batches[$sku] = (int)$quantity;
                }
            }

            // Flatten the response
            $response = [];
            foreach ($batches as $sku => $quantity) {
                $response[] = [
                    'sku' => $sku,
                    'name' => strtoupper($nameMap[$sku]),
                    'quantity' => $quantity
                ];
            }

            return new JsonResponse($response);
        });

        // @todo this stuff is only used for testing... remove this
        $controllers->match('/{location}/batches', function (Location $location, Request $request) use ($app) {
            /** @var BatchCollection $batches */
            $batches = $app['BatchCollectionFactory']
                ->makeFromArray($request->request);

            switch ($request->getMethod()) {
                // Merge the uploaded batch into the location
                case 'PATCH':
                    $typeName = 'batch merge';
                    $operation = new Operation($app['current_user'], null, $location, $batches);
                    break;
                // the Location batches are replaced by the uploaded ones
                // We achieve this by computing the difference and applying it with an operation
                // SOURCE + OP = TARGET
                // hence OP = TARGET - SOURCE
                case 'PUT':
                    $typeName = 'batch replace';
                    $diffBatches = $batches->diff($location->getBatches());
                    $operation = new Operation($app['current_user'], null, $location, $diffBatches);
                    break;
                default:
                    throw new \Exception('Method is not allowed');
            }

            $type = $app['em']->getRepository('Inventory:OperationType')->getByName($typeName);
            $operation->setType($type);

            $app['em']->persist($operation);
            $app['em']->flush();

            // @todo not atomical enough yet, but better
            if ($source = $operation->getSource()) {
                $app['em']->refresh($source);
            }
            if ($target = $operation->getTarget()) {
                $app['em']->refresh($target);
            }

            $operation->execute($app['current_user']);
            $app['em']->flush();

            // Is it merge or adjust ?
            return new JsonResponse(null, JsonResponse::HTTP_ACCEPTED);
        })->method('PATCH|PUT')->convert('location', $app['location.provider']());
        
        
        /*
         * Edit Batches in a given Location
         */
        $controllers->post('/{location}/batches', function (Location $location, Request $request) use ($app) {
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

            // Check for needed operations
            $pendingOperations = [];
            $pendingOperationAction = json_decode($request->get('pendingOperations'), true);
            $collateralOperationSets = [];
            // First check for operation fixing
            $ignoredOps = new OperationCollection();
            foreach($pendingOperationAction as $opType => $data) {
                $finder = new OperationFinder($app['em']);
                $pendingOperationsInLocation = $finder->manipulating($location)
                    ->isPending()
                    ->isType($opType)
                    ->withBatches() // only operation that moves batches are taken into account
                    ->find();
                if(count($pendingOperationsInLocation)) {
                    // This is to create a context for operations that were pending but
                    // there was an action to be taken on them
                    $collateralOperationSets[$data['action']] = new OperationSet($app['current_user']);
                    foreach($pendingOperationsInLocation as $pendingOp) {
                        $collateralOperationSets[$data['action']]->add($pendingOp);
                    }
                    $app['em']->persist($collateralOperationSets[$data['action']]);
                }

                switch($data['action']) {
                    case 'ignore':
                        $ignoredOps->merge(new OperationCollection($pendingOperationsInLocation));
                        break;
                    case 'execute':
                        foreach($pendingOperationsInLocation as $op) {
                            $op->execute($app['current_user']);                                
                        }
                        $app['em']->flush();
                        break;
                    case 'cancel':
                        foreach($pendingOperationsInLocation as $op) {
                            $op->cancel($app['current_user']);                                
                        }
                        $app['em']->flush();
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
            }

            if(!empty($pendingOperations)) {
                return new JsonResponse(['errors' => ['pendingOperations' => $pendingOperations]], 400);
            }

            // Create a BatchCollection out of a CSV file
            $batches = $app['BatchCollectionFactory']->makeFromArray($csv->data);

            // New operation set
            $set = new OperationSet(null);
            if ($description = $request->request->get('description')) {
                $set->setValue(['description' => $request->request->get('description')]);
            }

            // Find type
            switch ($request->request->get('type')) {
                case 'merge':
                    $typeName = 'batch merge';
                    $operation = new Operation($app['current_user'], null, $location, $batches);
                    break;
                case 'superReplace':
                    $typeName = 'batch superreplace';
                    $diffBatches = $batches->diff($location->getBatches());
                    $operation = new Operation($app['current_user'], null, $location, $diffBatches);
                    break;
                case 'replace':
                    $typeName = 'batch replace';
                    $diffBatches = $batches->diff($location->getBatches()->intersectWith($batches));
                    $operation = new Operation($app['current_user'], null, $location, $diffBatches);
                    break;
                default:
                    throw new \Exception('Type is unknown');
            }

            $type = $app['em']->getRepository('Inventory:OperationType')->getByName($typeName);
            $operation->setType($type);

            $app['em']->persist($operation);
            $operation->execute($app['current_user']);
            $set->add($operation);

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
            $app['em']->flush();

            return new Response('', Response::HTTP_ACCEPTED);
        })->convert('location', $app['location.provider']());

        /**
         * Get assigned modifiers to a specific Location
         */
        $controllers->get('/{code}/modifiers', function ($code, Application $app, Request $request) {
            $query = $app['em']->createQueryBuilder();
            $query->select('modifier, type, location')
                ->from(Modifier::class, 'modifier')
                ->innerJoin('modifier.location', 'location')
                ->innerJoin('modifier.type', 'type')
                ->andWhere('location.code = :code')
                ->setParameter('code', $code)
            ;

            $result = $query->getQuery()->execute();

            return new JsonResponse(
                array_map(function (\Silo\Inventory\Model\Modifier $modifier) {
                    return [
                        'name' => $modifier->getName(),
                        'value' => $modifier->getValue()
                    ];
                }, $result)
            );
        });

        /**
         * Create a Modifier
         * @todo Modifier value validation to implement
         */
        $controllers->post('/{location}/modifiers', function (Location $location, Request $request) use ($app) {
            /** @var ModifierRepository $modifiers */
            $modifiers = $app['re'](Modifier::class);
            $modifiers->add($location, $request->request->get('name'), $request->request->get('value'));
            $app['em']->flush();

            return new JsonResponse([], Response::HTTP_ACCEPTED);
        })
            ->convert('location', $app['location.provider']())
            ->before(new JsonRequest())
        ;

        /**
         * Remove a Modifier
         */
        $controllers->delete('/{code}/modifiers', function ($code, Request $request) use ($app) {
            $locations = $app['re']('Inventory:Location');

            $name = $request->request->get('name');
            if (!$name) {
                //throw new BadRequestHttpException();
            }
            /** @var Location $location */
            $location = $locations->forceFindOneByCode($code);
            /** @var ModifierRepository $modifiers */
            $modifiers = $app['re'](Modifier::class);
            $modifiers->remove($location, $name);
            $app['em']->flush();

            return new JsonResponse([], Response::HTTP_ACCEPTED);
        })->before(new JsonRequest());

        return $controllers;
    }
}
