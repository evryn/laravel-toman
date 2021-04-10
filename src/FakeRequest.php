<?php

namespace Evryn\LaravelToman;

use Evryn\LaravelToman\Exceptions\GatewayException;

class FakeRequest
{
    const SUCCESSFUL = 'successful';
    const FAILED = 'failed';

    private $status;
    private $transactionId;
    private $exception;

    public function successful(): self
    {
        $this->status = self::SUCCESSFUL;

        return $this;
    }

    public function failed($error = 'Stubbed payment failure.', $status = 400): self
    {
        $this->status = self::FAILED;

        $this->exception = new GatewayException($error, $status);

        return $this;
    }

    public function withTransactionId(string $transactionId)
    {
        $this->transactionId = $transactionId;

        return $this;
    }

    public function getTransactionId()
    {
        return $this->transactionId;
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
