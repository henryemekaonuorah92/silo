<?php

namespace Silo\Inventory;

use Doctrine\Common\Util\Debug;
use Silex\Application;
use Silex\Api\ControllerProviderInterface;
use Silo\Inventory\Model\Batch;
use Silo\Inventory\Model\BatchCollection;
use Silo\Inventory\Model\Location;
use Silo\Inventory\Model\Operation;
use Silo\Inventory\Model\User;
use Silo\Inventory\Validator\Constraints\LocationExists;
use Silo\Inventory\Validator\Constraints\SkuExists;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Validator\Constraints as Constraint;
use Symfony\Component\Validator\ConstraintViolationList;

/**
 * Endpoints
 */
class InventoryController implements ControllerProviderInterface
{
    public function connect(Application $app)
    {
        $controllers = $app['controllers_factory'];

        /**
         * Inspect a Location given its code
         */
        $controllers->get('/location/{code}', function ($code, Application $app) {
            $locations = $app['em']->getRepository('Inventory:Location');

            /** @var Location $location */
            if ($code == Location::CODE_ROOT) {
                $location = $locations->getSystemLocation($code);
            } else {
                $location = $locations->findOneByCode($code);
            }

            if (!$location) {
                throw new \Exception("Location $code does not exist");
            }

            $parent = $location->getParent();

            return new JsonResponse([
                'code' => $code,
                'parent' => $parent ? $parent->getCode() : null,
                'batches' => array_map(function(Batch $b){
                    return [
                        'product' => $b->getProduct()->getSku(),
                        'quantity' => $b->getQuantity()
                    ];
                }, array_slice($location->getBatches()->toArray(), 0, 5)),
                'childs' => array_map(function(Location $l){
                    return $l->getCode();
                }, $locations->findByParent($location))
            ]);
        });

        /**
         * Inspect a Location given its code
         */
        $controllers->get('/location/{code}/batches', function ($code, Application $app) {
            $locations = $app['em']->getRepository('Inventory:Location');

            /** @var Location $location */
            $location = $locations->findOneByCode($code);

            if (!$location) {
                throw new \Exception("Location $code does not exist");
            }

            return new JsonResponse(
                array_map(function(Batch $b){
                    return [
                        'product' => $b->getProduct()->getSku(),
                        'quantity' => $b->getQuantity()
                    ];
                }, array_slice($location->getBatches()->toArray(), 0, 5))
            );
        });

        /**
         * Create operations massively by uploading a CSV file
         */
        $controllers->post('/operation/import', function() use ($app){
            $csv = new \parseCSV($_FILES['file']['tmp_name']);
            $operationMap = [];
            $locations = $app['em']->getRepository('Inventory:Location');
            foreach ($csv->data as $line) {
                $line++;
                /** @var ConstraintViolationList $violations */
                $violations = $app['validator']->validate($line, [
                    new Constraint\Collection([
                        'source' => [new LocationExists()],
                        'target' => [new LocationExists()],
                        'sku' => [new Constraint\Required(), new SkuExists()],
                        'quantity' => new Constraint\Range(['min' => -100, 'max' => 100])
                    ])
                ]);

                if ($violations->count() > 0) {
                    return new JsonResponse(['errors' => array_map(function($violation){
                        return (string) $violation;
                    },iterator_to_array($violations->getIterator()))]);
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
                    null ;
                $target = !empty($targetCode) ?
                    $app['em']->getRepository('Inventory:Location')->findOneBy(['code' => $targetCode]) :
                    null ;

                $operation = new Operation($app['current_user'], $source, $target, $batches);
                $app['em']->persist($operation);
                $app['em']->flush();
                $operation->execute($app['current_user']);
                $app['em']->flush();
            }

            return new JsonResponse([]);
        });

        return $controllers;
    }
}