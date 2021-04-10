<?php

namespace Evryn\LaravelToman\Gateways\Zarinpal;

use Evryn\LaravelToman\Exceptions\GatewayClientException;
use Evryn\LaravelToman\Exceptions\GatewayServerException;
use Evryn\LaravelToman\FakeRequest;
use Illuminate\Support\Facades\Http;

/**
 * Class Requester.
 */
class PaymentRequest extends BaseRequest
{
    public function fakeFrom(FakeRequest $fakeRequest)
    {
        return new RequestedPayment(
            $fakeRequest->getException(),
            [],
            $fakeRequest->getTransactionId(),
            'example.com'
        );
    }

    public function request(): RequestedPayment
    {
        $response = Http::post(
            $this->getEndpoint('PaymentRequest'),
            $this->pendingRequest->provideForGateway([
                'merchantId',
                'callback',
                'description',
                'amount',
                'email',
                'mobile',
            ])->filter()->toArray()
        );

        $data = $response->json();

        // In case of connection issued. It indicates a proper time to switch gateway to
        // another provider.
        if ($response->serverError()) {
            return new RequestedPayment(
                new GatewayServerException(
                    'Unable to connect to ZarinPal endpoint due to server error.',
                    $response->status()
                )
            );
        }

        // Client errors (4xx) are not guaranteed to be come with error messages. We need to
        // check requested payment status too.
        if ($response->clientError() || $data['Status'] != Status::OPERATION_SUCCEED) {
            return new RequestedPayment(
                new GatewayClientException(
                    Status::toMessage($data['Status']),
                    $data['Status']
                ),
                $data['errors'] ?? []
            );
        }

        return new RequestedPayment(null, [], $data['Authority'], $this->getHost());
    }
}
