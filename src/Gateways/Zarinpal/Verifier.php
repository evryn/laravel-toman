<?php

namespace AmirrezaNasiri\LaravelToman\Gateways\Zarinpal;

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
           throw new GatewayException('تراکنش ناموفق بوده یا توسط کاربر لغو شده است.', -22);
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
        if ($status !== 100) {
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
            $message = $this->getStatusMessage($status);
        }

        throw new GatewayException($message, $status, $previous);
    }

    private function getStatusMessage($status)
    {
        // TODO: put in other class
        $messages = [
            '-1' => 'اطلاعات ارسال شده ناقص است.',
            '-2' => 'IP و يا مرچنت كد پذيرنده صحيح نيست',
            '-3' => 'با توجه به محدوديت هاي شاپرك امكان پرداخت با رقم درخواست شده ميسر نمي باشد',
            '-4' => 'سطح تاييد پذيرنده پايين تر از سطح نقره اي است.',
            '-11' => 'درخواست مورد نظر يافت نشد.',
            '-12' => 'امكان ويرايش درخواست ميسر نمي باشد.',
            '-21' => 'هيچ نوع عمليات مالي براي اين تراكنش يافت نشد',
            '-22' => 'تراکنش ناموفق بوده یا توسط کاربر لغو شده است.',
            '-33' => 'رقم تراكنش با رقم پرداخت شده مطابقت ندارد',
            '-34' => 'سقف تقسيم تراكنش از لحاظ تعداد يا رقم عبور نموده است',
            '-40' => 'اجازه دسترسي به متد مربوطه وجود ندارد.',
            '-41' => 'اطلاعات ارسال شده مربوط به AdditionalData غيرمعتبر ميباشد.',
            '-42' => 'مدت زمان معتبر طول عمر شناسه پرداخت بايد بين 30 دقيه تا 45 روز مي باشد.',
            '-54' => 'درخواست مورد نظر آرشيو شده است',
            '101' => 'عمليات پرداخت موفق بوده و قبلا PaymentVerification تراكنش انجام شده است.',
        ];

        return Arr::get($messages, $status, 'An unknown payment gateway error occurred.');
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
