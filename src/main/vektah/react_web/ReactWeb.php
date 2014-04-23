<?php

namespace vektah\react_web;

use React\Http\Request;
use React\Http\Response;
use React\Promise\PromiseInterface;
use vektah\common\json\Json;
use vektah\react_web\response\ControllerResponse;
use vektah\react_web\response\InternalServerError;
use vektah\react_web\response\PageNotFound;

/**
 * At some point adding annotations in front of this so that classes can configure their own routes would be nice.
 */
class ReactWeb
{
    private $routes = [];
    private $loop;

    public function __construct(LoopContext $loop)
    {
        $this->loop = $loop;
    }

    public function addRoute($route, callable $target)
    {
        $route = preg_replace_callback('/\{(?P<part>[a-zA-Z0-9\-_\.]*)\}/', function ($matches) {
            return "(?P<{$matches['part']}>[a-zA-Z0-9\\-_\\.]*)";
        }, $route);
        $route = str_replace('$', '\$', $route);
        $this->routes[$route] = $target;
    }

    public function dispatch(Request $request, Response $response)
    {
        echo $request->getPath()."\n";
        foreach ($this->routes as $route => $target) {
            if (preg_match("|^$route$|", $request->getPath(), $matches)) {
                $result = call_user_func($target, $matches);

                if ($result instanceof PromiseInterface) {
                    $result->then(function ($result) use ($request, $response) {
                        $this->completeResponse($response, $result);
                    }, function ($reason) use ($request, $response) {
                        $this->completeResponse($response, new InternalServerError($reason));
                    });
                    return;
                }

                $this->completeResponse($response, $result);
                return;
            }
        }

        $this->completeResponse($response, new PageNotFound());
    }

    private function completeResponse(Response $response, $result)
    {
        if ($result instanceof ControllerResponse) {
            $result->send($response);
        } else {
            $result = Json::pretty($result);

            $response->writeHead(200, ['Content-Type' => 'application/json']);
            $response->end($result);
        }
    }
}
