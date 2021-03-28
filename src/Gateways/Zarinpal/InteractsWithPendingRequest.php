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
        if ($this->pendingRequest->config('sandbox') === true) {
            return true;
        }

        return false;
    }

    /**
     * Get Zarinpal merchant ID from config or overridden one.
     *
     * @return string
     */
    private function getMerchantId()
    {
        $merchantId = $this->pendingRequest->data('MerchantID');

        return $merchantId ?? $this->pendingRequest->config('merchant_id');
    }
}
