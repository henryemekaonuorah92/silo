<?php

namespace Silo\Inventory;

use Doctrine\ORM\QueryBuilder;
use Silex\Application;
use Silex\Api\ControllerProviderInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Endpoints.
 */
class UserController implements ControllerProviderInterface
{
    public function connect(Application $app)
    {
        $controllers = $app['controllers_factory'];

        $controllers->post('/search', function (Request $request) use ($app) {
            $code = $request->query->get('query');
            /** @var QueryBuilder $query */
            $query = $app['em']->createQueryBuilder();
            $query->select('User.name')->from('Inventory:User', 'User')
                ->andWhere($query->expr()->like('User.name', ':code'))
                ->setParameter('code', "%$code%");

            return new JsonResponse(array_map(
                function ($l) {
                    return utf8_encode($l['name']);
                },
                $query->getQuery()->getArrayResult()
            ), Response::HTTP_ACCEPTED);
        });

        return $controllers;
    }
}
