<?php

namespace Silo\Base;

use Silex\Application;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * @seealso http://silex.sensiolabs.org/doc/cookbook/json_request_body.html
 */
class JsonRequest
{
    public function __invoke(Request $request, Application $app)
    {
        // If the request is not a GET, check if the request body is a valid json
        if (0 === strpos($request->headers->get('Content-Type'), 'application/json') && !$request->isMethod('GET')) {
            $data = json_decode($request->getContent(), true);
            if ($data === null && $request->getContent() !== "null") {
                return new JsonResponse(null, JsonResponse::HTTP_BAD_REQUEST);
            }
            $request->attributes->set('body', $data);
            $request->request->replace(is_array($data) ? $data : array());
        }
    }
}
