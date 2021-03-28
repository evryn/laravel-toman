<?php

namespace Evryn\LaravelToman\Gateways\Zarinpal;

use Evryn\LaravelToman\Exceptions\GatewayClientException;
use Evryn\LaravelToman\Exceptions\GatewayServerException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\URL;

/**
 * Class Requester.
 */
class RequestFactory
{
    use InteractsWithPendingRequest;

    /**
     * @var PendingRequest
     */
    private $pendingRequest;

    /**
     * Requester constructor.
     */
    public function __construct(PendingRequest $pendingRequest)
    {
        $this->pendingRequest = $pendingRequest;
    }

    public function request(): RequestedPayment
    {
        $response = Http::post($this->makeRequestURL(), $this->makeRequestData());
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

    /**
     * Make environment-aware verification endpoint URL.
     * @return string
     */
    private function makeRequestURL()
    {
        return $this->getHost().'/pg/rest/WebGate/PaymentRequest.json';
    }

    /**
     * Make config-aware verification endpoint required data.
     * @return array
     */
    private function makeRequestData()
    {
        return array_merge($this->pendingRequest->data(), [
            'MerchantID' => $this->getMerchantId(),
            'CallbackURL' => $this->getCallbackUrl(),
            'Description' => $this->getDescription(),
        ]);
    }

    private function getCallbackUrl()
    {
        if ($callbackUrlData = $this->pendingRequest->data('CallbackURL')) {
            return $callbackUrlData;
        }

        if ($defaultRoute = config('toman.callback_route')) {
            return URL::route($defaultRoute);
        }

        return null;
    }

    private function getDescription()
    {
        return str_replace(
            ':amount',
            $this->pendingRequest->data('Amount'),
            $this->pendingRequest->data('Description') ?: config('toman.description')
        );
    }
}
