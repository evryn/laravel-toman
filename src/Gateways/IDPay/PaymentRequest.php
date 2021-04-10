<?php

namespace Evryn\LaravelToman\Gateways\IDPay;

use Evryn\LaravelToman\Exceptions\GatewayClientException;
use Evryn\LaravelToman\Exceptions\GatewayServerException;
use Evryn\LaravelToman\FakeRequest;
use Illuminate\Http\Response;
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
            'https://idpay.ir/p/ws-sandbox/'.$fakeRequest->getTransactionId()
        );
    }

    public function request(): RequestedPayment
    {
        $response = Http::asJson()->withHeaders($this->makeHeaders())->post(
            $this->getEndpoint('payment'),
            $this->pendingRequest->provideForGateway([
                'callback',
                'orderId',
                'amount',
                'description',
                'email',
                'mobile',
                'name',
            ])->filter()->toArray()
        );

        $data = $response->json();

        // If response has created status, it means the payment has been created
        // successfully.
        if ($response->status() === Response::HTTP_CREATED) {
            return new RequestedPayment(null, [], $data['id'], $data['link']);
        }

        // Client errors (4xx) are not guaranteed to be come with error messages. We need to
        // check requested payment status too.
        if ($response->clientError()) {
            return new RequestedPayment(
                new GatewayClientException(
                    $data['error_message'],
                    $data['error_code']
                )
            );
        }

        // In case of connection issued. It indicates a proper time to switch gateway to
        // another provider.
        return new RequestedPayment(
            new GatewayServerException(
                'Unable to connect to IDPay endpoint due to unexpected error.',
                $response->status()
            )
        );
    }
}
