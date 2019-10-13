<?php

namespace AmirrezaNasiri\LaravelToman\Tests\Gateways;

use AmirrezaNasiri\LaravelToman\Tests\TestCase;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Request;
use Illuminate\Support\Arr;

abstract class DriverTestCase extends TestCase
{
    protected $history = [];

    protected function mockedGuzzleClient($responses)
    {
        $historyMiddleware = Middleware::history($this->history);
        $handlerStack      = HandlerStack::create();
        if ($responses) {
            $response_mock = new MockHandler(Arr::wrap($responses));
            $handlerStack  = HandlerStack::create($response_mock);
        }
        $handlerStack->push($historyMiddleware);

        return new Client(['handler' => $handlerStack]);;
    }

    /**
     * @return Request
     */
    protected function getLastRequest()
    {
        return Arr::last($this->history)['request'];
    }

    protected function getLastRequestURL()
    {
        return (string)$this->getLastRequest()->getUri();
    }

    protected function getLastRequestData($key = null)
    {
        $data = json_decode($this->getLastRequest()->getBody()->getContents(), true);
        return $key ? Arr::get($data, $key) : $data;
    }

    protected function assertLastRequestedDataEquals($expected)
    {
        self::assertEquals($expected, $this->getLastRequestData());
    }

    protected function assertDataInRequest($expected, $key)
    {
        self::assertEquals($expected, $this->getLastRequestData($key));
    }
}
