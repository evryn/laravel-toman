<?php

namespace Evryn\LaravelToman;

use Evryn\LaravelToman\Exceptions\GatewayException;
use Illuminate\Support\Facades\Http;

class FakeRequest
{
    const SUCCESSFUL = 'successful';
    const FAILED = 'failed';

    private $status;
    private $transactionId;
    private $referenceId;
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

    public function withReferenceId(string $referenceId)
    {
        $this->referenceId = $referenceId;

        return $this;
    }

    public function getTransactionId()
    {
        return $this->transactionId;
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