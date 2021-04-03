<?php

namespace Evryn\LaravelToman\Interfaces;

use Evryn\LaravelToman\Exceptions\GatewayException;
use Evryn\LaravelToman\FakeRequest;
use Evryn\LaravelToman\FakeVerification;
use Evryn\LaravelToman\PendingRequest;
use Illuminate\Http\RedirectResponse;

interface GatewayInterface
{
    public function getConfig(string $key = null);
    public function aliasData(string $conventional): ?string;

    /**
     * Make a real payment request or generate result based on a fake one
     *
     * @param PendingRequest $pendingRequest
     * @param FakeRequest|null $fakeRequest
     * @return RequestedPaymentInterface
     */
    public function requestPayment(PendingRequest $pendingRequest, FakeRequest $fakeRequest = null): RequestedPaymentInterface;

    /**
     * Make a real payment verification request or generate result based on a fake one
     *
     * @param PendingRequest $pendingRequest
     * @param FakeVerification|null $fakeVerification
     * @return CheckedPaymentInterface
     */
    public function verifyPayment(PendingRequest $pendingRequest, FakeVerification $fakeVerification = null): CheckedPaymentInterface;
}
