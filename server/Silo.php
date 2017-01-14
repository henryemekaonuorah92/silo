<?php

namespace Silo;

use Doctrine\ORM\EntityManager;
use Silo\Base\ConstraintValidatorFactory;
use Silo\Inventory\Model\User;
use Silo\Inventory\ProductProviderInterface;
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

        if (isset($values['em']) && !$values['em'] instanceof EntityManager) {
            throw new \Exception('em should be an EntityManager');
        }
        if (isset($values['productProvider']) && !$values['productProvider'] instanceof ProductProviderInterface) {
            throw new \Exception('productProvider should be a ProductProviderInterface');
        }
        if (isset($values['current_user']) &&
            is_object($values['current_user']) &&
            !$values['current_user'] instanceof User) {
            throw new \Exception('current_user should be an User');
        }

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

        $app->get('/silo/', function(){
            return "hello world";
        });

        $app->mount('/silo/inventory', new \Silo\Inventory\InventoryController());

        // Deal with exceptions
        $app->error(function (\Exception $e, $request) {
            return new Response($e, Response::HTTP_INTERNAL_SERVER_ERROR);
        });
    }
}
