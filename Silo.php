<?php

namespace Silo;

use Silo\Inventory\LocationWalker;
use Symfony\Component\HttpFoundation\Response;

/**
 * Main Silo entry point, exposed as a Container
 */
class Silo extends \Silex\Application
{
    /**
     * {@inheritdoc}
     */
    public function __construct(array $values = array())
    {
        if (!ini_get('date.timezone')) {
            ini_set('date.timezone', 'UTC');
        }

        parent::__construct($values);

        $app = $this;

        if (!$app->offsetExists('em')) {
            $app->register(new \Silo\Base\Provider\DoctrineProvider([
                __DIR__.'/Inventory/Model'
            ]));
        }

        $app['LocationWalker'] = function()use($app){
            return new LocationWalker($app['em']);
        };

        $app->mount('/silo/doc', new \Silo\Base\DocController());

        $app->get('/silo/hello', function() {
            return 'Hello World';
        });

        // Deal with exceptions
        $app->error(function (\Exception $e, $request) {
            return new Response($e, "500");
        });
    }
}