<?php

namespace Evryn\LaravelToman\Gateways\Zarinpal;

use Evryn\LaravelToman\Interfaces\RequestedPaymentInterface;
use Illuminate\Http\RedirectResponse;

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

    public function __construct(string $transactionId, string $baseUrl)
    {
        $this->transactionId = $transactionId;
        $this->baseUrl = $baseUrl;
    }

    public function getTransactionId(): string
    {
        return $this->transactionId;
    }

    /**
     * Get the payment URL specified to this payment request. User must be redirected
     * there to complete the payment.
     *
     * @param array $options ZarinPal accepts `gateway` option to target a specific bank gateway. Contact with their support for more info.
     * @return string
     */
    public function getPaymentUrl(array $options = []): string
    {
        $gateway = isset($options['gateway']) ? "/{$options['gateway']}" : '';

        return "{$this->baseUrl}/pg/StartPay/{$this->transactionId}{$gateway}";
    }

    /**
     * Redirect user to payment gateway to complete it.
     * @param array $options
     * @return RedirectResponse
     */
    public function pay(array $options = []): RedirectResponse
    {
        return redirect()->to($this->getPaymentUrl($options));
    }
}
