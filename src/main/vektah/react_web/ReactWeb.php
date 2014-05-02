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
    private $debug;

    public function __construct(LoopContext $loop, $debug = false)
    {
        $this->loop = $loop;
        $this->debug = $debug;
    }

    public function addRoute($method, $route, callable $target)
    {
        $route = preg_replace_callback('/\{(?P<part>[a-zA-Z0-9\-_\.]*)\}/', function ($matches) {
            return "(?P<{$matches['part']}>[a-zA-Z0-9\\-_\\.@%]*)";
        }, $route);
        $route = str_replace('$', '\$', $route);
        $this->routes[$method][$route] = $target;
    }

    public function dispatch(Request $request, Response $response)
    {
        // Todo PSR logging
        if ($this->debug) {
            echo date('r') . ": {$request->getPath()}:{$request->getMethod()}\n";
        }


        if (!isset($this->routes[$request->getMethod()])) {
            $this->completeResponse($response, new PageNotFound());
            return;
        }

        $routes = $this->routes[$request->getMethod()];

        foreach ($routes as $route => $target) {
            if (preg_match("|^$route$|", $request->getPath(), $matches)) {
                foreach ($matches as &$match) {
                    $match = urldecode($match);
                }

                try {
                    $result = call_user_func($target, $matches, $request);
                } catch (\Exception $e) {
                    $this->completeResponse($response, new InternalServerError($e));
                    return;
                }

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
        // Todo PSR logging
        if ($this->debug) {
            echo date('r') . ": -> ";
            print_r($result);
        }

        if ($result instanceof ControllerResponse) {
            $result->send($response);
        } else {
            $result = Json::pretty($result);

            $response->writeHead(200, ['Content-Type' => 'application/json']);
            $response->end($result);
        }
    }
}
