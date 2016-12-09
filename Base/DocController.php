<?php

namespace Silo\Base;

use Silex\Application;
use Silex\Api\ControllerProviderInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

class DocController implements ControllerProviderInterface
{
    public function connect(Application $app)
    {
        // creates a new controller based on the default route
        $controllers = $app['controllers_factory'];

        $controllers->get('/{path}', function ($path, Application $app) {
            $content = "Content not found";
            $file = __DIR__.'/../../../../../doc/'.strtolower($path).".md";
            if (file_exists($file)) {
                $content = file_get_contents($file);
            }

            return new JsonResponse(['content' => $content]);
        })->assert('path', '.+');

        return $controllers;
    }
}