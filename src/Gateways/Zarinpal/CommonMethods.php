<?php


namespace Evryn\LaravelToman\Gateways\Zarinpal;


use Evryn\LaravelToman\Exceptions\InvalidConfigException;

trait CommonMethods
{
    /**
     * Set <i>Amount</i> data
     * @param $amount string|integer|float
     * @return $this
     */
    public function amount($amount)
    {
        $this->data('Amount', $amount);

        return $this;
    }

    /**
     * Get production or sandbox schema and hostname for requests
     *
     * @return string
     * @throws InvalidConfigException
     */
    private function getHost()
    {
        $subDomain = $this->isSandbox() ? 'sandbox' : 'www';

        return "https://{$subDomain}.zarinpal.com";
    }

    /**
     * Check if request should be sent in a sandbox, testing environment
     *
     * @return bool
     * @throws InvalidConfigException
     */
    private function isSandbox()
    {
        $sandbox = $this->getConfig('sandbox');

        if ($sandbox === null || $sandbox === false) {
            return false;
        } elseif ($sandbox === true) {
            return true;
        }

        throw new InvalidConfigException('sandbox');
    }

    /**
     * Get Zarinpal merchant ID from config or overridden one
     *
     * @return string
     */
    private function getMerchantId()
    {
        $merchantId = $this->getData('MerchantID');
        return $merchantId ?? $this->getConfig('merchant_id');
    }

}
