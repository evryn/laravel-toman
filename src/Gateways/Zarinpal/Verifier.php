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
use AmirrezaNasiri\LaravelToman\Helpers\Client as ClientHelper;
use AmirrezaNasiri\LaravelToman\Helpers\Gateway as GatewayHelper;
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
            GatewayHelper::fail($exception);
        }

        $data = ClientHelper::getResponseData($response);

        $status = $data['Status'];
        if ($status !== Status::PAYMENT_SUCCEED) {
            GatewayHelper::fail($data);
        }

        return new VerifiedPayment($data['RefID']);
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
