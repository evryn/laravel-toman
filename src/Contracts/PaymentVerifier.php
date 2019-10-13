<?php

namespace AmirrezaNasiri\LaravelToman\Contracts;

use AmirrezaNasiri\LaravelToman\Results\VerifiedPayment;
use AmirrezaNasiri\LaravelToman\Results\RequestedPayment;
use Illuminate\Http\Request;

interface PaymentVerifier
{
    /**
     * Set driver-specific data parameter on-the-fly.
     * @param $key
     * @param null $value
     * @return mixed
     */
    public function data($key, $value = null);

    /**
     * Get all driver-specific data or specific key.
     * @param null $key
     * @return mixed
     */
    public function getData($key = null);

    /**
     * Verify payment
     * @return mixed
     */
    public function verify(Request $request): VerifiedPayment;
}
