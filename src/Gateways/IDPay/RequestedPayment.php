<?php

namespace Evryn\LaravelToman\Gateways\IDPay;

use Evryn\LaravelToman\Concerns\RequestedPayment as BaseRequestedPayment;
use Evryn\LaravelToman\Exceptions\GatewayException;

class RequestedPayment extends BaseRequestedPayment
{
    /**
     * @var string
     */
    protected $transactionUrl;

    public function __construct(GatewayException $exception = null, array $messages = [], $transactionId = null, string $transactionUrl = null)
    {
        $this->transactionId = $transactionId;
        $this->transactionUrl = $transactionUrl;
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
     * @param  array  $options  No option is available for this gateway
     * @return string
     */
    public function paymentUrl(array $options = []): ?string
    {
        if ($this->failed()) {
            $this->throw();
        }

        return $this->transactionUrl;
    }
}
