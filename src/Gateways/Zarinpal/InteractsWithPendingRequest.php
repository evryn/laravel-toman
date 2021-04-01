<?php


namespace Evryn\LaravelToman\Gateways\Zarinpal;


trait InteractsWithPendingRequest
{
    /**
     * Get production or sandbox schema and hostname for requests.
     *
     * @return string
     */
    private function getHost()
    {
        $subDomain = $this->isSandbox() ? 'sandbox' : 'www';

        return "https://{$subDomain}.zarinpal.com";
    }

    /**
     * Check if request should be sent in a sandbox, testing environment.
     *
     * @return bool
     */
    private function isSandbox()
    {
        return $this->pendingRequest->getGateway()->getConfig('sandbox') === true;
    }

    /**
     * Get Zarinpal merchant ID from config or overridden one.
     *
     * @return string
     */
    private function getMerchantId()
    {
        return $this->pendingRequest->merchantId() ?: $this->pendingRequest->getGateway()->getConfig('merchant_id');
    }
}
