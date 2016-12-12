<?php

namespace Silo\Inventory;

use Silex\Application;
use Silex\Api\ControllerProviderInterface;
use Silo\Inventory\Model\Batch;
use Silo\Inventory\Model\Location;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Endpoints
 */
class InventoryController implements ControllerProviderInterface
{
    public function connect(Application $app)
    {
        $controllers = $app['controllers_factory'];

        // Get a documentation page
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
                }, $location->getBatches()->toArray()),
                'childs' => array_map(function(Location $l){
                    return $l->getCode();
                }, $locations->findByParent($location))
            ]);
        })->assert('path', '.+');

        return $controllers;
    }
}