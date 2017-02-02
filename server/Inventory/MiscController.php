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

class MiscController implements ControllerProviderInterface
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

        return $controllers;
    }
}
