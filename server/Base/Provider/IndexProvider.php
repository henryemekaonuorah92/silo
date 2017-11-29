<?php

namespace Silo\Base\Provider;

use Pimple\Container;
use Silex\Api\ControllerProviderInterface;
use Silex\Application;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class IndexProvider implements \Pimple\ServiceProviderInterface, ControllerProviderInterface
{
    public function register(Container $app)
    {
        $app['index.css'] = [
            '/master.css',
            '/static/default/default/css/chosen.css',
            '/static/default/default/css/jquery.switchButton.css'
        ];

        $app['index.js'] = [
            '/legacy.js',
            '/vendors.js',
            '/app.js'
        ];

        $app['index.response'] = $app->factory(function()use($app){

            $metas = "";
            $version = $app['version'];
            foreach($app['index.css'] as $cssScript) {
                $metas.=sprintf('<link rel="stylesheet" href="%s" type="text/css" />'.PHP_EOL, $cssScript.'?v='.urlencode($version));
            }
            foreach($app['index.js'] as $jsScript) {
                $metas.=sprintf('<script type="text/javascript" src="%s"></script>'.PHP_EOL, $jsScript.'?v='.urlencode($version));
            }

            return new Response(<<<HTML
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>silo</title>
    <link rel="icon" type="image/png" sizes="16x16" href="/favicon-16x16.png">
    <link rel="icon" type="image/png" sizes="32x32" href="/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="96x96" href="/favicon-96x96.png">
</head>
<body>
    <div id="container"></div>
    $metas
</body>
</html>
HTML
            );
        });
    }


    /**
     * {@inheritdoc}
     */
    public function connect(Application $app)
    {
        $c = $app['controllers_factory'];

        $c->get('/', $app['index.response']);

        return $c;
    }
}
