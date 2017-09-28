<?php

namespace Silo\Search;

use Debug\Searcher;
use Doctrine\Common\Collections\ArrayCollection;
use Pimple\Container;
use Pimple\ServiceProviderInterface;
use Silex\Api\EventListenerProviderInterface;
use Silo\Base\EntityManagerAware;
use Silo\Base\JsonRequest;
use Silo\Inventory\Model\Location;
use Silo\Inventory\Model\Operation;
use Silo\Inventory\Model\Product;
use Silo\Search\Searcher\LocationSearcher;
use Silo\Search\Searcher\PrimaryKey;
use Silo\Search\Searcher\PrimaryKeySearcher;
use Silo\Search\Searcher\ProductSku;
use Silo\Search\Searcher\ProductSkuSearcher;
use Silo\Search\Searcher\SearchResult;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Able to identify business objects that could match a query.
 * Queries are often identifiers, SKUs, LPN codes and so on.
 * Supports a disambiguation system.
 */
class SearchProvider implements ServiceProviderInterface
{
    public function register(Container $app)
    {
        /*
            'Application\Doctrine\Model\ShippingOrder' => 'silo/order/%shippingOrderId%'
        */

        $app['search.bits'] = function(){return new ArrayCollection([
            new PrimaryKeySearcher(Operation::class, 'operation', ['id' => 'id']),
            new LocationSearcher(),
            new ProductSkuSearcher()
        ]);};

        $app->post('/silo/search', function(Request $request)use($app){
            $query = $request->get('query');
            $results = [];
            foreach ($app['search.bits'] as $searcher) {
                $searcher->setEntityManager($app['em']);
                $searcher->setUrlGenerator($app['url_generator']);
                $r = $searcher->search($query);
                if ($r) {
                    $results+=$r;
                }
            }

            return new JsonResponse(['candidates' => array_map(function(SearchResult $s){
                return [
                    'url' => $s->getUrl(),
                    'anchor' => $s->getDescription()
                ];
            }, $results)]);
        })->before(new JsonRequest());
    }
}
