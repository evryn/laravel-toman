<?php

namespace Evryn\LaravelToman\Gateways\Zarinpal;

use Evryn\LaravelToman\Exceptions\GatewayException;
use Evryn\LaravelToman\Interfaces\CheckedPaymentInterface;
use Evryn\LaravelToman\Interfaces\RequestedPaymentInterface;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;

class CheckedPayment implements CheckedPaymentInterface
{
    /**
     * @var string
     */
    private $referenceId;
    /**
     * @var GatewayException|null
     */
    private $exception;
    /**
     * @var array
     */
    private $messages;
    /**
     * @var string
     */
    private $status;
    /**
     * @var null
     */
    private $transactionId;

    public function __construct(string $status, GatewayException $exception = null, array $messages = [], $transactionId = null, $referenceId = null)
    {
        $this->referenceId = $referenceId;
        $this->exception = $exception;
        $this->messages = $messages;
        $this->status = $status;
        $this->transactionId = $transactionId;
    }

    public function successful(): bool
    {
        return (int) $this->status === Status::OPERATION_SUCCEED;
    }

    public function alreadyVerified(): bool
    {
        return (int) $this->status === Status::ALREADY_VERIFIED;
    }

    public function failed(): bool
    {
        return !!$this->exception;
    }

    public function throw(): void
    {
        if ($this->failed()) {
            throw $this->exception;
        }
    }

    public function status()
    {
        return $this->status;
    }

    public function message(): ?string
    {
        return Arr::first($this->messages());
    }

    public function messages(): array
    {
        if ($this->messages) {
            return $this->messages;
        }

        if ($this->failed()) {
            return [$this->exception->getMessage()];
        }

        return [];
    }

    public function referenceId(): ?string
    {
        if ($this->failed()) {
            $this->throw();
        }

        return $this->referenceId;
    }

    public function transactionId(): string
    {
        return $this->transactionId;
    }
}
