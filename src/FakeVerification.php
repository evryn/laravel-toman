<?php

namespace Evryn\LaravelToman;

use Evryn\LaravelToman\Exceptions\GatewayException;

class FakeVerification
{
    const SUCCESSFUL = 'successful';
    const ALREADY_VERIFIED = 'already_verified';
    const FAILED = 'failed';

    private $transactionId;
    private $orderId;
    private $referenceId;
    private $status;
    private $exception;

    public function successful(): self
    {
        $this->status = self::SUCCESSFUL;

        return $this;
    }

    public function alreadyVerified(): self
    {
        $this->status = self::ALREADY_VERIFIED;

        return $this;
    }

    public function failed($error = 'Stubbed payment failure.', $status = 400): self
    {
        $this->status = self::FAILED;
        $this->exception = new GatewayException($error, $status);

        return $this;
    }

    public function withTransactionId(string $transactionId): self
    {
        $this->transactionId = $transactionId;

        return $this;
    }

    public function withOrderId(string $orderId): self
    {
        $this->orderId = $orderId;

        return $this;
    }

    public function withReferenceId(string $referenceId): self
    {
        $this->referenceId = $referenceId;

        return $this;
    }

    public function getTransactionId()
    {
        return $this->transactionId;
    }

    public function getOrderId()
    {
        return $this->orderId;
    }

    public function getReferenceId()
    {
        return $this->referenceId;
    }

    public function getException()
    {
        return $this->exception;
    }

    public function getStatus()
    {
        return $this->status;
    }
}
