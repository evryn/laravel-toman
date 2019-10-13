<?php

namespace AmirrezaNasiri\LaravelToman\Gateways\Zarinpal;

use AmirrezaNasiri\LaravelToman\Tests\Gateways\Zarinpal\Status;
use AmirrezaNasiri\LaravelToman\VerifiedPayment;
use AmirrezaNasiri\LaravelToman\Gateways\BaseRequester;
use AmirrezaNasiri\LaravelToman\Gateways\BaseVerifier;
use AmirrezaNasiri\LaravelToman\RequestedPayment;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ServerException;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use GuzzleHttp\RequestOptions;
use Illuminate\Support\Facades\URL;
use AmirrezaNasiri\LaravelToman\Utils;
use GuzzleHttp\Exception\ClientException;
use AmirrezaNasiri\LaravelToman\Exceptions\GatewayException;
use AmirrezaNasiri\LaravelToman\Exceptions\InvalidConfigException;

class Verifier extends BaseVerifier
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

    public function amount($amount)
    {
        $this->data('Amount', $amount);

        return $this;
    }

    public function verify(Request $request): VerifiedPayment
    {
       if ($request->input('Status') !== 'OK') {
           // TODO: magic number
           throw new GatewayException(Status::toMessage(Status::NOT_PAID), Status::NOT_PAID);
       }

        try {
            $response = $this->client->post(
                $this->makeVerificationURL(),
                [ RequestOptions::JSON => $this->makeVerificationData($request) ]
            );
        } catch (ClientException | ServerException $exception) {
           $this->throwGatewayException($exception);
        }

        $data = Utils::getResponseData($response);

        $status = $data['Status'];
        if ($status !== Status::PAYMENT_SUCCEED) {
            $this->throwGatewayException($data);
        }

        return new VerifiedPayment($data['RefID']);
    }

    /**
     * @param array|ClientException|\Exception $data
     * @throws GatewayException
     */
    private function throwGatewayException($data)
    {
        $previous = null;
        if ($data instanceof ClientException) {
            $previous = $data;
            $data = Utils::getResponseData($data);
        }

        $status = Arr::get($data, 'Status', 0);

        if ($errors = Arr::get($data, 'errors')) {
            $message = Arr::flatten($errors)[0];
        } else {
            $message = Status::toMessage($status);
        }

        throw new GatewayException($message, $status, $previous);
    }

    private function makeVerificationURL()
    {
        return $this->getHost().'/pg/rest/WebGate/PaymentVerification.json';
    }

    private function getHost()
    {
        $subdomain = $this->isSandbox() ? 'sandbox' : 'www';

        return "https://{$subdomain}.zarinpal.com";
    }

    private function makeVerificationData(Request $request)
    {
        return array_merge($this->data, [
            'MerchantID' => $this->getMerchantId(),
            'Authority' => $request->input('Authority'),
        ]);
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
        $merchantId = $this->getData('MerchantID'); // TODO: simplify

        if (! $merchantId) {
            $merchantId = $this->getConfig('merchant_id');
        }

        return $merchantId;
    }
}
