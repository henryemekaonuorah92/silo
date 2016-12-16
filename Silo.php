<?php

namespace Silo;

use Silo\Base\ConstraintValidatorFactory;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\Validation;

/**
 * Main Silo entry point, exposed as a Container.
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
                __DIR__.'/Inventory/Model',
                __DIR__.'/Order/Model',
            ]));
        }

        $app['validator'] = function() use ($app) {
            return Validation::createValidatorBuilder()
                ->addMethodMapping('loadValidatorMetadata')
                ->setConstraintValidatorFactory(new ConstraintValidatorFactory($app))
                ->getValidator();
        };

        $app->mount('/silo/doc', new \Silo\Base\DocController());
        $app->mount('/silo/inventory', new \Silo\Inventory\InventoryController());

        $app->get('/silo/hello', function () {
            return 'Hello World';
        });

        // Deal with exceptions
        $app->error(function (\Exception $e, $request) {
            return new Response($e, '500');
        });
    }
}
