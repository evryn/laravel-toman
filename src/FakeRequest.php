<?php

namespace Evryn\LaravelToman;

use Evryn\LaravelToman\Exceptions\GatewayException;

class FakeRequest
{
    private $transactionId;
    private $exception;

    public function successful(string $transactionId = 'T10001000'): self
    {
        $this->transactionId = $transactionId;

        return $this;
    }

    public function failed($error = 'Stubbed payment failure.', $status = 400): self
    {
        $this->exception = new GatewayException($error, $status);

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
}
