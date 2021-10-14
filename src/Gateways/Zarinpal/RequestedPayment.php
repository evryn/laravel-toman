<?php

namespace Evryn\LaravelToman\Gateways\Zarinpal;

use Evryn\LaravelToman\Concerns\RequestedPayment as BaseRequestedPayment;
use Evryn\LaravelToman\Exceptions\GatewayException;

class RequestedPayment extends BaseRequestedPayment
{
    /**
     * @var string
     */
    protected $baseUrl;

    public function __construct(GatewayException $exception = null, array $messages = [], $transactionId = null, string $baseUrl = null)
    {
        $this->transactionId = $transactionId;
        $this->baseUrl = $baseUrl;
        $this->exception = $exception;
        $this->messages = $messages;
    }

    public function successful(): bool
    {
        return ! $this->exception;
    }

    public function failed(): bool
    {
        return ! $this->successful();
    }

    /**
     * Get the payment URL specified to this payment request. User must be redirected
     * there to complete the payment.
     *
     * @param  array  $options  ZarinPal accepts `gateway` option to target a specific bank gateway. Contact with their support for more info.
     * @return string
     */
    public function paymentUrl(array $options = []): ?string
    {
        $gateway = isset($options['gateway']) ? "/{$options['gateway']}" : '';

        return "{$this->baseUrl}/pg/StartPay/{$this->transactionId()}{$gateway}";
    }
}
