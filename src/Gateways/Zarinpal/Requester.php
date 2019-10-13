<?php

namespace AmirrezaNasiri\LaravelToman\Gateways\Zarinpal;

use AmirrezaNasiri\LaravelToman\Gateways\BaseRequester;
use AmirrezaNasiri\LaravelToman\RequestedPayment;
use AmirrezaNasiri\LaravelToman\Tests\Gateways\Zarinpal\Status;
use GuzzleHttp\Client;
use Illuminate\Support\Arr;
use GuzzleHttp\RequestOptions;
use Illuminate\Support\Facades\URL;
use AmirrezaNasiri\LaravelToman\Helpers\Client as ClientHelper;
use AmirrezaNasiri\LaravelToman\Helpers\Gateway as GatewayHelper;
use AmirrezaNasiri\LaravelToman\Exceptions\InvalidConfigException;

class Requester extends BaseRequester
{
    public function __construct($config, Client $client)
    {
        $this->setConfig($config);
        $this->client = $client;
    }

    public static function make($config, Client $client)
    {
        return new self($config, $client);
    }

    public function callback($callbackUrl)
    {
        $this->data('CallbackURL', $callbackUrl);

        return $this;
    }

    public function amount($amount)
    {
        $this->data('Amount', $amount);

        return $this;
    }

    public function mobile($mobile)
    {
        $this->data('Mobile', $mobile);

        return $this;
    }

    public function email($email)
    {
        $this->data('Email', $email);

        return $this;
    }

    public function description($description)
    {
        $this->data('Description', $description);

        return $this;
    }

    public function request(): RequestedPayment
    {
        $requestData = $this->makeRequestData();
        $requestURL = $this->makeRequestURL();
        try {
            $response = $this->client->post(
                $requestURL,
                [RequestOptions::JSON => $requestData]
            );
        } catch (\Exception $exception) {
            GatewayHelper::fail($exception);
        }

        $data = ClientHelper::getResponseData($response);

        $transactionId = Arr::get($data, 'Authority');

        if (Arr::get($data, 'Status') !== Status::PAYMENT_SUCCEED || ! $transactionId) {
            GatewayHelper::fail($data);
        }

        return new RequestedPayment($transactionId, $this->getPaymentUrlFor($transactionId));
    }

    public function getPaymentUrlFor($transactionId)
    {
        return $this->getHost()."/pg/StartPay/{$transactionId}";
    }

    private function makeRequestURL()
    {
        return $this->getHost().'/pg/rest/WebGate/PaymentRequest.json';
    }

    private function getHost()
    {
        $subdomain = $this->isSandbox() ? 'sandbox' : 'www';

        return "https://{$subdomain}.zarinpal.com";
    }

    private function makeRequestData()
    {
        return array_merge($this->data, [
            'MerchantID' => $this->getMerchantId(),
            'CallbackURL' => $this->getCallbackUrl(),
            'Description' => $this->getDescription(),
        ]);
    }

    private function getCallbackUrl()
    {
        if ($data = $this->getData('CallbackURL')) {
            return $data;
        }

        if ($defaultRoute = config('toman.callback_route')) {
            return URL::route($defaultRoute);
        }
    }

    private function getDescription()
    {
        $description = $this->getData('Description');

        if (! $description) {
            $description = config('toman.description');
        }

        return str_replace(':amount', $this->getData('Amount'), $description);
    }

    private function isSandbox()
    {
        $sandbox = $this->getConfig('sandbox');

        if ($sandbox === null || $sandbox === false) {
            return false;
        } elseif ($sandbox === true) {
            return true;
        }

        throw new InvalidConfigException('sandbox');
    }

    private function getMerchantId()
    {
        $merchantId = $this->getData('MerchantID');

        if (! $merchantId) {
            $merchantId = $this->getConfig('merchant_id');
        }

        return $merchantId;
    }
}
