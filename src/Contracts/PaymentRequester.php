<?php

namespace Evryn\LaravelToman\Contracts;

use Evryn\LaravelToman\Results\RequestedPayment;

interface PaymentRequester
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
     * Callback URL used to return user after payment by the gateway.
     * @param string $callbackUrl
     * @return mixed
     */
    public function callback(string $callbackUrl);

    /**
     * Amount to be paid by user in gateway-specific unit (Rial/Toman).
     * @param $amount
     * @return mixed
     */
    public function amount($amount);

    /**
     * Payment description.
     * @param $description
     * @return mixed
     */
    public function description($description);

    /**
     * Request new payment transaction from gateway provider.
     * @return mixed
     */
    public function request(): RequestedPayment;
}
