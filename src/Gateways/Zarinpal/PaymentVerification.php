<?php

namespace Evryn\LaravelToman\Gateways\Zarinpal;

use Evryn\LaravelToman\Exceptions\GatewayClientException;
use Evryn\LaravelToman\Exceptions\GatewayServerException;
use Evryn\LaravelToman\FakeVerification;
use Illuminate\Support\Facades\Http;

/**
 * Class Requester.
 */
class PaymentVerification extends BaseRequest
{
    public function fakeFrom(FakeVerification $fakeVerification)
    {
        $this->prepareRequestData();

        $status = null;

        if ($fakeVerification->getStatus() === $fakeVerification::FAILED) {
            $status = Status::FAILED_TRANSACTION;
        }

        if ($fakeVerification->getStatus() === $fakeVerification::SUCCESSFUL) {
            $status = Status::OPERATION_SUCCEED;
        }

        if ($fakeVerification->getStatus() === $fakeVerification::ALREADY_VERIFIED) {
            $status = Status::ALREADY_VERIFIED;
        }

        return new CheckedPayment(
            $status,
            $fakeVerification->getException(),
            [],
            $fakeVerification->getTransactionId(),
            $fakeVerification->getReferenceId()
        );
    }

    public function verify(): CheckedPayment
    {
        $this->prepareRequestData();

        $response = Http::post($this->getEndpoint('PaymentVerification'), $this->pendingRequest->data());
        $data = $response->json();
        $status = $data['Status'] ?? null;

        // In case of connection issued. It indicates a proper time to switch gateway to
        // another provider.
        if ($response->serverError()) {
            return new CheckedPayment(
                $response->status(),
                new GatewayServerException(
                    'Unable to connect to ZarinPal endpoint due to server error.',
                    $response->status()
                ),
                [],
                $this->getTransactionId(),
                null
            );
        }

        // Client errors (4xx) are not guaranteed to be come with error messages. We need to
        // check requested payment status too.
        if ($response->clientError() || !in_array($status, [Status::OPERATION_SUCCEED, Status::ALREADY_VERIFIED])) {
            return new CheckedPayment(
                $status,
                new GatewayClientException(
                    Status::toMessage($status) ,
                    $status
                ),
                $data['errors'] ?? [],
                $this->getTransactionId(),
                null
            );
        }

        return new CheckedPayment($status, null, [], $this->getTransactionId(), $data['RefID']);
    }

    /**
     * Make config-aware verification endpoint required data.
     */
    private function prepareRequestData()
    {
        $this->pendingRequest->merchantId($this->getMerchantId());
        $this->pendingRequest->transactionId($this->getTransactionId());
    }

    private function getTransactionId()
    {
        if ($transactionId = $this->pendingRequest->transactionId()) {
            return $transactionId;
        }

        if (request()->has('Authority')) {
            request()->validate(['Authority' => 'required|string']);

            return request('Authority');
        }

        return null;
    }
}
