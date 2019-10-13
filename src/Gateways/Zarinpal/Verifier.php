<?php

namespace Evryn\LaravelToman\Gateways\Zarinpal;

use Evryn\LaravelToman\Tests\Gateways\Zarinpal\Status;
use Evryn\LaravelToman\Results\VerifiedPayment;
use Evryn\LaravelToman\Gateways\BaseVerifier;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ServerException;
use Illuminate\Http\Request;
use GuzzleHttp\RequestOptions;
use Evryn\LaravelToman\Helpers\Client as ClientHelper;
use Evryn\LaravelToman\Helpers\Gateway as GatewayHelper;
use GuzzleHttp\Exception\ClientException;
use Evryn\LaravelToman\Exceptions\GatewayException;
use Evryn\LaravelToman\Exceptions\InvalidConfigException;

/**
 * Class Verifier
 * @package Evryn\LaravelToman\Gateways\Zarinpal
 */
class Verifier extends BaseVerifier
{
    use CommonMethods;

    /**
     * Verifier constructor.
     * @param $config
     * @param Client $client
     */
    public function __construct($config, Client $client)
    {
        $this->setConfig($config);
        $this->client = $client;
    }

    /**
     * Initialize a Requester object on-the-fly
     * @param $config
     * @param Client $client
     * @return self
     */
    public static function make($config, Client $client)
    {
        return new self($config, $client);
    }

    /**
     * Verify incoming payment callback and get reference ID of transaction if possible
     * @param Request $request Current HTTP request
     * @return VerifiedPayment If payment is verified
     * @throws GatewayException If payment is not verified
     * @throws InvalidConfigException
     */
    public function verify(Request $request): VerifiedPayment
    {
       if ($request->input('Status') !== 'OK') {
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

    /**
     * Make environment-aware verification endpoint URL
     * @return string
     * @throws InvalidConfigException
     */
    private function makeVerificationURL()
    {
        return $this->getHost().'/pg/rest/WebGate/PaymentVerification.json';
    }

    /**
     * Make config-aware verification endpoint required data
     * @param Request $request
     * @return array
     */
    private function makeVerificationData(Request $request)
    {
        return array_merge($this->data, [
            'MerchantID' => $this->getMerchantId(),
            'Authority' => $request->input('Authority'),
        ]);
    }
}
