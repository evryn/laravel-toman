<?php

namespace Evryn\LaravelToman\Gateways\IDPay;

use Evryn\LaravelToman\PendingRequest;

abstract class BaseRequest
{
    /**
     * @var PendingRequest
     */
    protected $pendingRequest;

    /**
     * Requester constructor.
     */
    public function __construct(PendingRequest $pendingRequest)
    {
        $this->pendingRequest = $pendingRequest;
    }

    /**
     * Make request headers.
     *
     * @return array
     */
    protected function makeHeaders(): array
    {
        $headers = [
            'X-API-KEY' => $this->pendingRequest->merchantId(),
        ];

        if ($this->pendingRequest->getGateway()->getConfig('sandbox') === true) {
            $headers['X-SANDBOX'] = true;
        }

        return $headers;
    }

    /**
     * Make environment-aware verification endpoint URL.
     *
     * @param  string  $method
     * @return string
     */
    protected function getEndpoint(string $method): string
    {
        return "https://api.idpay.ir/v1.1/{$method}";
    }
}
