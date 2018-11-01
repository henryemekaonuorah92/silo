<?php

namespace Silo\Middleware;

use Silex\Application;
use Symfony\Component\HttpFoundation\Request;

class SkuUrlDecode
{
    public function __invoke(Request $request, Application $app)
    {

        if ($sku = $request->attributes->get('sku')) {
            $request->attributes->set('sku', urldecode($sku));
        }

        if ($product = $request->attributes->get('product')) {
            $request->attributes->set('product', urldecode($product));
        }

    }

}