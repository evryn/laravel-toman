<?php

namespace Evryn\LaravelToman\Interfaces;

use Evryn\LaravelToman\FakeRequest;
use Evryn\LaravelToman\FakeVerification;
use Evryn\LaravelToman\PendingRequest;

interface GatewayInterface
{
    public function getConfig(string $key = null);

    /**
     * Get the currency that gateway uses.
     *
     * @return string
     */
    public function getCurrency(): string;

    public function getAliasDataFields(): array;

    /**
     * Make a real payment request or generate result based on a fake one.
     *
     * @param  PendingRequest  $pendingRequest
     * @param  FakeRequest|null  $fakeRequest
     * @return RequestedPaymentInterface
     */
    public function requestPayment(PendingRequest $pendingRequest, FakeRequest $fakeRequest = null): RequestedPaymentInterface;

    /**
     * Make a real payment verification request or generate result based on a fake one.
     *
     * @param  PendingRequest  $pendingRequest
     * @param  FakeVerification|null  $fakeVerification
     * @return CheckedPaymentInterface
     */
    public function verifyPayment(PendingRequest $pendingRequest, FakeVerification $fakeVerification = null): CheckedPaymentInterface;

    /**
     * Inspect callback request by validating it and filling given pending request with
     * proper values from the callback or the stubbed fake verification.
     *
     * @param  PendingRequest  $pendingRequest
     */
    public function inspectCallbackRequest(PendingRequest $pendingRequest, FakeVerification $fakeVerification = null): void;
}
