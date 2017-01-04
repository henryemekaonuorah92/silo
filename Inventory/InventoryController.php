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
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
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
            $location = $locations->forceFindOneByCode($code);

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
            $location = $locations->forceFindOneByCode($code);

            return new JsonResponse(
                array_map(function(Batch $b){
                    return [
                        'product' => $b->getProduct()->getSku(),
                        'quantity' => $b->getQuantity()
                    ];
                }, $location->getBatches()->toArray())
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

        /**
         * Create operations massively by uploading a CSV file
         */
        $controllers->post('/location/{code}/batches', function ($code, Application $app, Request $request) {
            $locations = $app['em']->getRepository('Inventory:Location');
            /** @var Location $location */
            $location = $locations->forceFindOneByCode($code);
            $csv = new \parseCSV($_FILES['file']['tmp_name']);

            // Create a BatchCollection out of a CSV file
            $batches = new BatchCollection();
            foreach ($csv->data as $line) {
                $line++;
                /** @var ConstraintViolationList $violations */
                $violations = $app['validator']->validate($line, [
                    new Constraint\Collection([
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

                $batches->addBatch($batch);
            }

            switch($request->get('merge')) {
                // Merge the uploaded batch into the location
                case 'true':
                    $operation = new Operation($app['current_user'], null, $location, $batches);
                    break;
                // the Location batches are replaced by the uploaded ones
                // We achieve this by computing the difference and applying it with an operation
                // SOURCE + OP = TARGET
                // hence OP = TARGET - SOURCE
                case 'false':
                    $diffBatches = $batches->diff($location->getBatches());
                    $operation = new Operation($app['current_user'], null, $location, $diffBatches);
                    break;
                default:
                    throw new \Exception("merge parameter should be present and be either true or false");
            }

            $app['em']->persist($operation);
            $app['em']->flush();
            $operation->execute($app['current_user']);
            $app['em']->flush();

            // Is it merge or adjust ?
            return new Response('', Response::HTTP_ACCEPTED);
        });

        return $controllers;
    }
}