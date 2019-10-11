<?php

namespace AmirrezaNasiri\LaravelToman;

use Illuminate\Http\RedirectResponse;

class PaymentRequest
{
    private $transactionId;
    private $paymentUrl;

    public function __construct($transactionId, $paymentUrl)
    {
        $this->transactionId = $transactionId;
        $this->paymentUrl = $paymentUrl;
    }

    /**
     * Get transaction ID provided by gateway for newly requested payment. You'll
     * need it to verify actual payment.
     * <ul>
     * <li>In Zarinpal, it's the <i>Authority ID</i> in the documentations</li>
     * </ul>
     *
     * @return mixed
     */
    public function getTransactionId()
    {
        return $this->transactionId;
    }

    /**
     * Get the payment URL specified to this payment request. User must be redirected
     * there to complete the payment.
     *
     * @return string
     */
    public function getPaymentUrl(): string
    {
        return $this->paymentUrl;
    }

    /**
     * Redirect user to payment gateway to complete it
     * @return RedirectResponse
     */
    public function pay(): RedirectResponse
    {
        return redirect()->to($this->getPaymentUrl());
    }
}
