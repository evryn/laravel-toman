<?php


namespace AmirrezaNasiri\LaravelToman;


use GuzzleHttp\Exception\ClientException;
use Psr\Http\Message\ResponseInterface;

class Utils
{
    /**
     * @param ResponseInterface|ClientException $response
     * @return array|null
     */
    public static function getResponseData($response)
    {
        if ($response instanceof ClientException){
            $response = $response->getResponse();
        }

        return json_decode($response->getBody()->getContents(), true);
    }
}
