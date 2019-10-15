<?php

namespace Evryn\LaravelToman\Helpers;

use Evryn\LaravelToman\Gateways\Zarinpal\Status;
use Illuminate\Support\Arr;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ServerException;
use Evryn\LaravelToman\Exceptions\GatewayException;
use Evryn\LaravelToman\Helpers\Client as ClientHelper;

class Gateway
{
    /**
     * Throws a Gateway exception from any possible input.
     * @param array|ClientException|ServerException|\Exception $data
     * @throws GatewayException
     */
    public static function zarinFails($data)
    {
        $previous = null;
        if ($data instanceof ClientException) {
            $previous = $data;
            $data = ClientHelper::getResponseData($data);
        }

        $status = Arr::get($data, 'Status', 0);

        if ($errors = Arr::get($data, 'errors')) {
            $message = Arr::flatten($errors)[0];
        } else {
            $message = Status::toMessage($status);
        }

        throw new GatewayException($message, $status, $previous);
    }
}
