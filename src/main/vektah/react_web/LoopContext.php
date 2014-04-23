<?php


namespace vektah\react_web;

use React\Dns\Resolver\Factory as DnsFactory;
use React\Dns\Resolver\Resolver;
use React\HttpClient\Client;
use React\HttpClient\Factory as HttpClientFactory;
use React\EventLoop\LoopInterface;

class LoopContext
{
    /** @var LoopInterface */
    private $loop;

    /** @var  Resolver */
    private $dns;

    private $resolver = '8.8.8.8';

    private $http_client;

    public function __construct($loop, $resolver = null)
    {
        $this->loop = $loop;

        if ($resolver) {
            $this->resolver = $resolver;
        }
    }

    /**
     * @return \React\EventLoop\LoopInterface
     */
    public function getLoop()
    {
        return $this->loop;
    }

    /**
     * @return Resolver
     */
    public function getDns()
    {
        if (!$this->dns) {
            $factory = new DnsFactory();
            $this->dns = $factory->createCached($this->resolver, $this->getLoop());
        }

        return $this->dns;
    }

    /**
     * @return Client
     */
    public function getHttpClient()
    {
        if (!$this->http_client) {
            $factory = new HttpClientFactory();
            $this->http_client = $factory->create($this->getLoop(), $this->getDns());
        }

        return $this->http_client;
    }
}
