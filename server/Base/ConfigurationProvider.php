<?php

namespace Silo\Base;

use Doctrine\Common\Cache\ArrayCache;
use Doctrine\Common\Cache\PhpFileCache;
use Doctrine\Common\Collections\ArrayCollection;
use Flintstone\Flintstone;
use Pimple\Container;
use Silo\Base\JsonRequest;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Writable configuration exposed through an API endpoint
 *
 * Inspiration coming from igorw/config-service-provider
 *
 * A key is declared through $app['config']->declare(name, value);
 * which is quite like $app[name] = value;
 *
 * It can exists prior to invocation
 *
 */
class ConfigurationProvider implements \Pimple\ServiceProviderInterface
{
    private $configuration;

    public function register(Container $app)
    {
        if (!isset($app['config.cache'])) {
            $app['config.cache'] = new ArrayCache(); // This cache is not useful at all
        }

        /**
         * Keys that should be visible and editable in configuration calls
         * Each key receives a Validation chain if needed, and a default value
         */
        $app['config'] = function()use($app){
            return new Configuration($app, $app['config.cache']);
        };

        $app->get('/silo/config', function()use($app){
            return new JsonResponse($app['config']->getAll());
        });

        // @todo validation
        $app->post('/silo/config', function(Request $request)use($app){

            $data = $request->attributes->get('body');
            foreach ($data as $key => $value) {
                $app['config']->set($key, $value);
            }
            $app['config']->save();

            return new JsonResponse(null);
        })->before(new JsonRequest);


    }
}
