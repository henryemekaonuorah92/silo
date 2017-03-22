<?php

namespace Silo;

use Doctrine\ORM\EntityManager;
use Silo\Base\ConstraintValidatorFactory;
use Silo\Base\Provider\DoctrineProvider\SQLLogger;
use Silo\Base\ValidationException;
use Silo\Inventory\BatchCollectionFactory;
use Silo\Inventory\Model\User;
use Silo\Inventory\OperationValidator;
use Silo\Inventory\ProductProviderInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Validator\Exception\ValidatorException;
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
        } else {
            $app['em_logger'] = function () use ($app) {
                return new SQLLogger();
            };
            $app['em']->getConnection()
                ->getConfiguration()
                ->setSQLLogger($app['em_logger']);
        }

        // Shortcut for getting a Repository instance quick
        $app['re'] = $app->protect(function ($name) use ($app) {
            return $app['em']->getRepository($name);
        });

        $app['validator'] = function () use ($app) {
            return Validation::createValidatorBuilder()
                ->addMethodMapping('loadValidatorMetadata')
                ->setConstraintValidatorFactory(new ConstraintValidatorFactory($app))
                ->getValidator();
        };

        if (!$app->offsetExists('OperationValidator')) {
            $app['OperationValidator'] = function () use ($app) {
                return new OperationValidator();
            };
        }

        $app['BatchCollectionFactory'] = function () use ($app) {
            return new BatchCollectionFactory($app['em'], $app['validator']);
        };

        $app->mount('/silo/inventory/location', new \Silo\Inventory\LocationController());
        $app->mount('/silo/inventory/operation', new \Silo\Inventory\OperationController());
        $app->mount('/silo/inventory/product', new \Silo\Inventory\ProductController());

        // Deal with exceptions
        $app->error(function (\Exception $e, $request) use ($app){
            if ($e instanceof NotFoundHttpException) {
                return new JsonResponse($e->getMessage(), JsonResponse::HTTP_NOT_FOUND);
            }
            if ($e instanceof ValidationException) {
                return new JsonResponse(['errors' => array_map(function ($violation) {
                    return (string) $violation;
                }, iterator_to_array($e->getViolations()->getIterator()))], JsonResponse::HTTP_BAD_REQUEST);
            }

            if ($app['logger']) {
                $app['logger']->error($e);
            }
            return new JsonResponse([
                'message' => $e->getMessage(),
                'trace' => $e->getTrace(),
                'file' => $e->getFile().':'.$e->getLine()
            ], JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
        });
    }
}
