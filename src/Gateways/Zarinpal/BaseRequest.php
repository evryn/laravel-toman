<?php

namespace Evryn\LaravelToman\Gateways\Zarinpal;

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
     * Check if request should be sent in a sandbox, testing environment.
     *
     * @return bool
     */
    protected function isSandbox()
    {
        return $this->pendingRequest->getGateway()->getConfig('sandbox') === true;
    }

    /**
     * Make environment-aware verification endpoint URL.
     *
     * @param  string  $method
     * @return string
     */
    protected function getEndpoint(string $method)
    {
        return $this->getHost()."/pg/rest/WebGate/{$method}.json";
    }

    /**
     * Get production or sandbox schema and hostname for requests.
     *
     * @return string
     */
    protected function getHost()
    {
        $subDomain = $this->isSandbox() ? 'sandbox' : 'www';

        return "https://{$subDomain}.zarinpal.com";
    }
}
