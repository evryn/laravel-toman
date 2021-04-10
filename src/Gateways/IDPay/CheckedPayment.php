<?php

namespace Evryn\LaravelToman\Gateways\IDPay;

use Evryn\LaravelToman\Concerns\CheckedPayment as BaseCheckedPayment;
use Evryn\LaravelToman\Exceptions\GatewayException;

class CheckedPayment extends BaseCheckedPayment
{
    /**
     * @var string
     */
    protected $status;
    /**
     * @var null
     */
    private $orderId;

    public function __construct(string $status, GatewayException $exception = null, array $messages = [], $orderId = null, $transactionId = null, $referenceId = null)
    {
        $this->referenceId = $referenceId;
        $this->exception = $exception;
        $this->messages = $messages;
        $this->status = $status;
        $this->transactionId = $transactionId;
        $this->orderId = $orderId;
    }

    public function status()
    {
        return $this->status;
    }

    public function orderId(): string
    {
        return $this->orderId;
    }

    public function successful(): bool
    {
        return (int) $this->status === Status::SUCCESSFUL;
    }

    public function alreadyVerified(): bool
    {
        return (int) $this->status === Status::ALREADY_VERIFIED;
    }

    public function failed(): bool
    {
        return (bool) $this->exception;
    }
}
