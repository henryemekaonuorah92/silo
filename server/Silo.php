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
        // @todo Should be a check
        // if (!ini_get('date.timezone')) {
        //    ini_set('date.timezone', 'UTC');
        //}

        parent::__construct($values);

        $app = $this;

        if (!$app->offsetExists('em')) {
            $app->register(new \Silo\Base\Provider\DoctrineProvider([
                __DIR__.'/Inventory/Model',
            ]));
        }

        $app['validator'] = function () use ($app) {
            return Validation::createValidatorBuilder()
                ->addMethodMapping('loadValidatorMetadata')
                ->setConstraintValidatorFactory(new ConstraintValidatorFactory($app))
                ->getValidator();
        };

        $app->mount('/silo/inventory', new \Silo\Inventory\InventoryController());

        // Deal with exceptions
        $app->error(function (\Exception $e, $request) use ($app){
            if ($app->offsetExists('logger')) {
                $app['logger']->error($e);
            }
            return new Response($e, Response::HTTP_INTERNAL_SERVER_ERROR);
        });
    }
}
