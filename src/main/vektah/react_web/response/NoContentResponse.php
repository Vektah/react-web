<?php


namespace vektah\react_web\response;

use React\Http\Response;

class NoContentResponse implements ControllerResponse
{
    public function send(Response $response)
    {
        $response->writeHead(204);
        $response->end();
    }
}
