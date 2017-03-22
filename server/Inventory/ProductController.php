<?php

namespace Silo\Inventory;

use Silex\Application;
use Silex\Api\ControllerProviderInterface;
use Silo\Base\JsonRequest;
use Silo\Inventory\Model\Batch;
use Silo\Inventory\Collection\BatchCollection;
use Silo\Inventory\Model\Location;
use Silo\Inventory\Model\Modifier;
use Silo\Inventory\Model\Operation;
use Silo\Inventory\Model\Product;
use Silo\Inventory\Repository\ModifierRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Endpoints.
 *
 * @todo should factorize this a bit
 */
class ProductController implements ControllerProviderInterface
{
    public function connect(Application $app)
    {
        $controllers = $app['controllers_factory'];

        $products = $app['em']->getRepository('Inventory:Product');
        $productProvider = function ($sku) use ($products) {
            $product = $products->findOneBySku($sku);
            if (!$product) {
                throw new NotFoundHttpException("Product:$sku cannot be found");
            }

            return $product;
        };

        /*
         * Inspect a Product given its sku
         */
        $controllers->get('/{product}', function (Product $product, Application $app) {
            $query = $app['em']->createQueryBuilder();
            $query->select('ba, loc')
                ->from('Inventory:Batch', 'ba')
                ->innerJoin('ba.location', 'loc')
                ->leftJoin('loc.modifiers', 'mod')
                ->leftJoin('mod.type', 'type')
                ->andWhere('ba.product = :product')
                ->setParameter('product', $product);

            /** @var Location[] $locations */
            $batches = $query->getQuery()->getResult();

            return new JsonResponse([
                'product' => $product->getSku(),
                'locations' => array_map(function(Batch $batch){
                    return [
                        'location' => $batch->getLocation()->getCode(),
                        'quantity' => $batch->getQuantity(),
                        'modifiers' => array_map(function(Modifier $mod){
                            return $mod->getName();
                        }, $batch->getLocation()->getModifiers()->toArray())
                    ];
                }, $batches)
            ]);
        })->convert('product', $productProvider);

        return $controllers;
    }
}
