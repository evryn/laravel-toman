<?php

namespace AmirrezaNasiri\LaravelToman;

use Psr\Http\Message\ResponseInterface;
use GuzzleHttp\Exception\ClientException;

class Utils
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
