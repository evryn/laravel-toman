<?php

namespace Evryn\LaravelToman\Gateways\Zarinpal;

use Evryn\LaravelToman\Exceptions\GatewayException;
use Evryn\LaravelToman\Interfaces\RequestedPaymentInterface;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;

class RequestedPayment implements RequestedPaymentInterface
{
    /**
     * @var string
     */
    private $transactionId;
    /**
     * @var string
     */
    private $baseUrl;
    /**
     * @var GatewayException|null
     */
    private $exception;
    /**
     * @var array
     */
    private $messages;

    public function __construct(GatewayException $exception = null, array $messages = [], $transactionId = null, string $baseUrl = null)
    {
        $this->transactionId = $transactionId;
        $this->baseUrl = $baseUrl;
        $this->exception = $exception;
        $this->messages = $messages;
    }

    public function successful(): bool
    {
        return !$this->exception;
    }

    public function failed(): bool
    {
        return !$this->successful();
    }

    public function throw(): void
    {
        if ($this->failed()) {
            throw $this->exception;
        }
    }

    public function status()
    {
        return $this->failed() ? $this->exception->getCode() : null;
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

    public function transactionId(): ?string
    {
        if ($this->failed()) {
            $this->throw();
        }

        return $this->transactionId;
    }

    /**
     * Get the payment URL specified to this payment request. User must be redirected
     * there to complete the payment.
     *
     * @param array $options ZarinPal accepts `gateway` option to target a specific bank gateway. Contact with their support for more info.
     * @return string
     */
    public function paymentUrl(array $options = []): ?string
    {
        $gateway = isset($options['gateway']) ? "/{$options['gateway']}" : '';

        return "{$this->baseUrl}/pg/StartPay/{$this->transactionId()}{$gateway}";
    }

    /**
     * Redirect user to payment gateway to complete it.
     * @param array $options
     * @return RedirectResponse
     */
    public function pay(array $options = []): RedirectResponse
    {
        return redirect()->to($this->paymentUrl($options));
    }
}
