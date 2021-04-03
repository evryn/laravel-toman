<?php


namespace Evryn\LaravelToman\Gateways\Zarinpal;


use Evryn\LaravelToman\FakeRequest;
use Evryn\LaravelToman\FakeVerification;
use Evryn\LaravelToman\Interfaces\CheckedPaymentInterface;
use Evryn\LaravelToman\Interfaces\GatewayInterface;
use Evryn\LaravelToman\Interfaces\RequestedPaymentInterface;
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

    public function setConfig(array $config)
    {
        $this->config = $config;
    }

    public function aliasData(string $conventional): ?string
    {
        return [
            'merchantid' => 'MerchantID',
            'amount' => 'Amount',
            'transactionid' => 'Authority',
            'callback' => 'CallbackURL',
            'mobile' => 'Mobile',
            'email' => 'Email',
            'description' => 'Description',
        ][strtolower($conventional)] ?? null;
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

    public function verifyPayment(PendingRequest $pendingRequest, FakeVerification $fakeVerification = null): CheckedPaymentInterface
    {
        $factory = (new PaymentVerification($pendingRequest));

        if ($fakeVerification) {
            return $factory->fakeFrom($fakeVerification);
        }

        return $factory->verify();
    }
}
