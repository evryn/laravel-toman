<?php

namespace Evryn\LaravelToman\Gateways\Zarinpal;

trait CommonMethods
{
    /**
     * Set <i>Amount</i> data.
     * @param $amount string|integer|float
     * @return $this
     */
    public function amount($amount)
    {
        $this->data('Amount', $amount);

        return $this;
    }

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
        if ($this->getConfig('sandbox') === true) {
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
        $merchantId = $this->getData('MerchantID');

        return $merchantId ?? $this->getConfig('merchant_id');
    }
}
