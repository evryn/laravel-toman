<?php

namespace Evryn\LaravelToman\Gateways\IDPay;

use Evryn\LaravelToman\FakeRequest;
use Evryn\LaravelToman\FakeVerification;
use Evryn\LaravelToman\Interfaces\CheckedPaymentInterface;
use Evryn\LaravelToman\Interfaces\GatewayInterface;
use Evryn\LaravelToman\Interfaces\RequestedPaymentInterface;
use Evryn\LaravelToman\Money;
use Evryn\LaravelToman\PendingRequest;
use Illuminate\Support\Arr;

class Gateway implements GatewayInterface
{
    /** @var array */
    private $config;

    public function __construct(array $config = [])
    {
        $this->config = $config;
    }

    public function getConfig(string $key = null)
    {
        return $key ? Arr::get($this->config, $key) : $this->config;
    }

    public function getCurrency(): string
    {
        return Money::RIAL;
    }

    public function setConfig(array $config)
    {
        $this->config = $config;
    }

    public function getAliasDataFields(): array
    {
        return [
            'merchantId' => 'api_key',
            'amount' => 'amount',
            'transactionId' => 'id',
            'orderId' => 'order_id',
            'callback' => 'callback',
            'mobile' => 'phone',
            'email' => 'mail',
            'description' => 'desc',
            'name' => 'name',
        ];
    }

    public function getMerchantIdData()
    {
        return Arr::get($this->config, 'api_key');
    }

    /** @inheritDoc */
    public function requestPayment(PendingRequest $pendingRequest, FakeRequest $fakeRequest = null): RequestedPaymentInterface
    {
        $factory = (new PaymentRequest($pendingRequest));

        if ($fakeRequest) {
            return $factory->fakeFrom($fakeRequest);
        }

        return $factory->request();
    }

    /** @inheritDoc */
    public function verifyPayment(PendingRequest $pendingRequest, FakeVerification $fakeVerification = null): CheckedPaymentInterface
    {
        $factory = (new PaymentVerification($pendingRequest));

        if ($fakeVerification) {
            return $factory->fakeFrom($fakeVerification);
        }

        return $factory->verify();
    }

    /** @inheritDoc */
    public function inspectCallbackRequest(PendingRequest $pendingRequest, FakeVerification $fakeVerification = null): void
    {
        $request = app(CallbackRequest::class);

        if ($fakeVerification) {
            $request->setFakeVerification($fakeVerification);
        }

        $request->validateCallback();

        $pendingRequest->orderId($request->getOrderId());
        $pendingRequest->transactionId($request->getTransactionId());
    }
}
