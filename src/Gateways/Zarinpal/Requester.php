<?php

namespace Evryn\LaravelToman\Gateways\Zarinpal;

use Evryn\LaravelToman\Exceptions\GatewayClientException;
use Evryn\LaravelToman\Exceptions\GatewayServerException;
use Evryn\LaravelToman\Interfaces\PaymentRequesterInterface;
use Evryn\LaravelToman\Interfaces\RequestedPaymentInterface;
use Evryn\LaravelToman\Support\HasConfig;
use Evryn\LaravelToman\Support\HasData;
use Evryn\LaravelToman\Support\RedirectsForPaying;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\URL;

/**
 * Class Requester.
 */
class Requester implements PaymentRequesterInterface
{
    use HasConfig, HasData, CommonMethods, RedirectsForPaying;

    /**
     * Requester constructor.
     * @param $config
     */
    public function __construct(array $config = [])
    {
        $this->setConfig($config);
    }

    /**
     * Set <i>CallbackURL</i> data and override config.
     * @param $callbackUrl string
     * @return $this
     */
    public function callback(string $callbackUrl): self
    {
        $this->data('CallbackURL', $callbackUrl);

        return $this;
    }

    /**
     * Set <i>Mobile</i> data.
     * @param $mobile string
     * @return $this
     */
    public function mobile(string $mobile): self
    {
        $this->data('Mobile', $mobile);

        return $this;
    }

    /**
     * Set <i>MerchantID</i> data.
     * @param string $merchantId
     * @return $this
     */
    public function merchantId(string $merchantId): self
    {
        $this->data('MerchantID', $merchantId);

        return $this;
    }

    /**
     * Set <i>Email</i> data.
     * @param $email string
     * @return $this
     */
    public function email(string $email): self
    {
        $this->data('Email', $email);

        return $this;
    }

    /**
     * Set <i>Description</i> data and override config.
     * @param string $description
     * @return $this
     */
    public function description(string $description): self
    {
        $this->data('Description', $description);

        return $this;
    }

    /**
     * Request a new payment from gateway.
     * @return RequestedPayment If new payment is created and is ready to pay
     * @throws \Evryn\LaravelToman\Exceptions\GatewayException If new payment was not created
     */
    public function request(): RequestedPaymentInterface
    {
        $response = Http::post($this->makeRequestURL(), $this->makeRequestData());
        $data = $response->json();

        // In case of connection issued. It indicates a proper time to switch gateway to
        // another provider.
        if ($response->serverError()) {
            throw new GatewayServerException(
                'Unable to connect to ZarinPal servers.',
                $response->status()
            );
        }

        // Client errors (4xx) are not guaranteed to be come with error messages. We need to
        // check requested payment status too.
        if ($response->clientError() || $data['Status'] != Status::OPERATION_SUCCEED) {
            throw new GatewayClientException(
                Status::toMessage($data['Status']),
                $data['Status']
            );
        }

        return new RequestedPayment($data['Authority'], $this->getHost());
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
        return array_merge($this->data, [
            'MerchantID' => $this->getMerchantId(),
            'CallbackURL' => $this->getCallbackUrl(),
            'Description' => $this->getDescription(),
        ]);
    }

    /**
     * Get 'CallbackURL' from data or default one from config if available.
     * @return array|mixed|string
     */
    private function getCallbackUrl(): string
    {
        if ($callbackUrl = $this->getData('CallbackURL')) {
            return $callbackUrl;
        }

        if ($defaultRoute = config('toman.callback_route')) {
            return URL::route($defaultRoute);
        }

        return '';
    }

    /**
     * Get 'Description' from data or default one from config if available.
     * @return array|mixed|string
     */
    private function getDescription(): string
    {
        $description = $this->getData('Description');

        if (!$description) {
            $description = config('toman.description');
        }

        return str_replace(':amount', $this->getData('Amount'), $description);
    }
}
