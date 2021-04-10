<?php

namespace Evryn\LaravelToman\Gateways\IDPay;

use Evryn\LaravelToman\Exceptions\GatewayClientException;
use Evryn\LaravelToman\Exceptions\GatewayServerException;
use Evryn\LaravelToman\FakeVerification;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Http;

/**
 * Class Requester.
 *
 * Required data for verification: `merchantId`, `transactionId` and `amount`
 */
class PaymentVerification extends BaseRequest
{
    public function fakeFrom(FakeVerification $fakeVerification)
    {
        $status = null;

        if ($fakeVerification->getStatus() === $fakeVerification::FAILED) {
            $status = Status::UNSUCCESSFUL_PAYMENT;
        }

        if ($fakeVerification->getStatus() === $fakeVerification::SUCCESSFUL) {
            $status = Status::SUCCESSFUL;
        }

        if ($fakeVerification->getStatus() === $fakeVerification::ALREADY_VERIFIED) {
            $status = Status::ALREADY_VERIFIED;
        }

        return new CheckedPayment(
            $status,
            $fakeVerification->getException(),
            [],
            $fakeVerification->getOrderId(),
            $fakeVerification->getTransactionId(),
            $fakeVerification->getReferenceId()
        );
    }

    public function verify(): CheckedPayment
    {
        $response = Http::asJson()->withHeaders($this->makeHeaders())->post(
            $this->getEndpoint('payment/verify'),
            $this->pendingRequest->provideForGateway([
                'transactionId',
                'orderId',
            ])->filter()->toArray()
        );

        $data = $response->json();

        // If payment was successful or already verified or paid
        if ($response->status() === Response::HTTP_OK) {
            return new CheckedPayment(
                $data['status'],
                null,
                [],
                $this->pendingRequest->orderId(),
                $this->pendingRequest->transactionId(),
                $data['payment']['track_id']
            );
        }

        // Client errors (4xx) are not guaranteed to be come with error messages. We need to
        // check requested payment status too.
        if ($response->clientError()) {
            return new CheckedPayment(
                $data['error_code'],
                new GatewayClientException(
                    $data['error_message'],
                    $data['error_code']
                ),
                [],
                $this->pendingRequest->orderId(),
                $this->pendingRequest->transactionId(),
                null
            );
        }

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
                $this->pendingRequest->orderId(),
                $this->pendingRequest->transactionId(),
                null
            );
        }
    }
}
