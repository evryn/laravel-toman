<?php

namespace AmirrezaNasiri\LaravelToman\Helpers;

use Psr\Http\Message\ResponseInterface;
use GuzzleHttp\Exception\ClientException;

class Client
{
    /**
     * @param ResponseInterface|ClientException $response
     * @return array|null
     */
    public static function getResponseData($response)
    {
        if ($response instanceof ClientException) {
            $response = $response->getResponse();
        }

        return json_decode($response->getBody()->getContents(), true);
    }
}
