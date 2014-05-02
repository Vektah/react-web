<?php


namespace vektah\react_web\response;

use React\Http\Response;

class CreatedResponse implements ControllerResponse
{
    private $location;

    public function __construct($location)
    {
        $this->location = $location;
    }


    public function send(Response $response)
    {
        $response->writeHead(201, [
            'Content-type' => 'text/plain',
            'Location' => $this->location
        ]);
        $response->end();
    }
}
