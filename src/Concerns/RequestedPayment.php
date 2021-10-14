<?php

namespace Evryn\LaravelToman\Concerns;

use Evryn\LaravelToman\Interfaces\RequestedPaymentInterface;
use Illuminate\Http\RedirectResponse;

abstract class RequestedPayment implements RequestedPaymentInterface
{
    use InteractsWithResponse;

    /** @var string */
    protected $transactionId;

    public function transactionId(): ?string
    {
        if ($this->failed()) {
            $this->throw();
        }

        return $this->transactionId;
    }

    /**
     * Redirect user to payment gateway to complete it.
     *
     * @param  array  $options
     * @return RedirectResponse
     */
    public function pay(array $options = []): RedirectResponse
    {
        return redirect()->to($this->paymentUrl($options));
    }
}
