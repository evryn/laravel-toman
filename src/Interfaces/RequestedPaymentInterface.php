<?php

namespace Evryn\LaravelToman\Interfaces;

use Illuminate\Http\RedirectResponse;

interface RequestedPaymentInterface
{
    /**
     * Get payment transaction id that should be used for verification
     * @return string
     */
    public function getTransactionId(): string;

    /**
     * Get final payment URL that user should be redirected to
     * @param array $options
     * @return mixed
     */
    public function getPaymentUrl(array $options = []): string;

    /**
     * Redirect user to final payment URL
     * @param array $options
     * @return mixed
     */
    public function pay(array $options = []): RedirectResponse;
}
