<?php

namespace Evryn\LaravelToman\Interfaces;

use Evryn\LaravelToman\Exceptions\GatewayException;

/**
 * These are additional methods that are gateway-dependent:.
 *
 * @method string orderId() Get order id that is used for verification
 */
interface CheckedPaymentInterface
{
    /**
     * Determine if payment is requested successfully.
     *
     * @return bool
     */
    public function successful(): bool;

    /**
     * Determine if payment is requested successfully.
     *
     * @return bool
     */
    public function alreadyVerified(): bool;

    /**
     * Determine if there is an error with payment request.
     *
     * @return bool
     */
    public function failed(): bool;

    /**
     * Throw exception if request is failed for some reason.
     *
     * @throws GatewayException
     */
    public function throw(): void;

    /**
     * Get status of the payment request.
     *
     * @return int|string|void
     */
    public function status();

    /**
     * Get proper message for the payment request, if failed.
     *
     * @return null|string
     */
    public function message(): ?string;

    /**
     * Get all messages.
     *
     * @return array
     */
    public function messages(): array;

    /**
     * Get payment transaction id that should be used for verification.
     *
     * @return string
     */
    public function referenceId(): ?string;

    /**
     * Get payment transaction id that should be used for verification.
     *
     * @return string
     */
    public function transactionId(): string;
}
