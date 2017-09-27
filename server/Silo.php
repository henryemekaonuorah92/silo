<?php

namespace Silo;

use Doctrine\ORM\EntityManager;
use Silo\Base\ConfigurationProvider;
use Silo\Base\ConstraintValidatorFactory;
use Silo\Base\Provider\DoctrineProvider\SQLLogger;
use Silo\Base\Provider\MetricProvider;
use Silo\Base\ValidationException;
use Silo\Inventory\BatchCollectionFactory;
use Silo\Inventory\GC\BatchGarbageCollector;
use Silo\Inventory\GC\GarbageCollectorProvider;
use Silo\Inventory\Model\User;
use Silo\Inventory\OperationValidator;
use Silo\Inventory\ProductProviderInterface;
use Silo\Inventory\UserController;
use Silo\Inventory\Playbacker;
use Symfony\Component\Debug\ErrorHandler;
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
    public function __construct(array $values = [])
    {
        // @todo Should be a check
        // if (!ini_get('date.timezone')) {
        //    ini_set('date.timezone', 'UTC');
        //}

        parent::__construct($values);


        $this->register(new ConfigurationProvider($this));
        $this->register(new GarbageCollectorProvider);
        $this->register(new MetricProvider);
        $this->register(new \Silo\Base\Provider\DoctrineProvider, [
            'em.paths' => [__DIR__.'/Inventory/Model'],
        ]);



        $app = $this;

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
            return new BatchCollectionFactory(
                $app['em'],
                $app['validator'],
                isset($app['skuTransformer']) ? $app['skuTransformer'] : null
            );
        };

        $app['Playbacker'] = function () use ($app) {
            $s = new Playbacker();
            $s->setEntityManager($app['em']);
            return $s;
        };

        if (class_exists('\\Sorien\\Provider\\PimpleDumpProvider')) {
            //$app->register(new \Sorien\Provider\PimpleDumpProvider());
        }

        $app->mount('/silo/inventory/location', new \Silo\Inventory\LocationController());
        $app->mount('/silo/inventory/operation', new \Silo\Inventory\OperationController());
        $app->mount('/silo/inventory/product', new \Silo\Inventory\ProductController());
        $app->mount('/silo/inventory/batch', new \Silo\Inventory\BatchController());
        $app->mount('/silo/inventory/user', new \Silo\Inventory\UserController());
        $app->mount('/silo/inventory/export', new \Silo\Inventory\ExportController());

        // Deal with exceptions
        ErrorHandler::register();
        $app->error(function (\Exception $e, $request) use ($app) {
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
