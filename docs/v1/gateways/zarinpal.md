# Zarinpal Gateway

[Zarinpal.com](https://www.zarinpal.com) gateway was added in version 1.0 and implementation of it is based on version 1.3 of their [official document](https://github.com/ZarinPal-Lab/Documentation-PaymentGateway/).

## Config

Zarinpal gateway requires following variables in `.env` file to work:

| Environment Variable 	| Description                                                                                                                                                                                                                                        	|
|----------------------	|----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------	|
| TOMAN_GATEWAY 	    | (**Required**)<br>Must equal `zarinpal` in order to use this gateway provider.                                                                            	|
| ZARINPAL_MERCHANT_ID 	| (**Required**)<br>Your gateway's merchant ID which can be gotten from your Zarinpal panel.<br>Example: 0bcf346fc-3a79-4b36-b936-5ccbc2be0696                                                                                                             	|
| ZARINPAL_SANDBOX     	| (Optional. Default: false)<br>Set it to `true` to make test calls in a simulated environment provided by Zarinpal without actual payments.<br>Delete the key or set it to `false` to make calls in production environment and receive actual payments. 	|

Example:
```dotenv
...

TOMAN_GATEWAY=zarinpal
ZARINPAL_MERCHANT_ID=0bcf346fc-3a79-4b36-b936-5ccbc2be0696
```

## Payment Request

In short, you can request a new payment with the following methods and catches:
```php
use Evryn\LaravelToman\Facades\PaymentRequest;
use Evryn\LaravelToman\Gateways\Zarinpal\Status;

// ...

try {
    $requestedPayment = PaymentRequest::amount(1000)
        ->description('Subscrbing to Plan A')
        ->callback('https://example.com/callback')
        ->mobile('09350000000')
        ->email('amirreza@example.com')
        ->request();
} catch (GatewayException $gatewayException) {
    if ($gatewayException->getCode() === Status::SHAPARAK_LIMITED) {
        // there might be a problem with 'Amount' data.
    }
} catch (InvalidConfigException $exception) {
    // Woops! wrong merchant_id or sandbox option is configurated.
}

// Use $requestedPayment...
```

| Method      	| Description                                                                                                                     	|
|-------------	|---------------------------------------------------------------------------------------------------------------------------------	|
| callback    	| Sets `CallbackURL` data and overrides `callback_route` config.                                                                  	|
| description 	| Sets `Description` data and overrides `description` config.                                                                     	|
| mobile      	| Sets `Mobile` data.                                                                                                             	|
| email       	| Sets `Email` data.                                                                                                              	|
| amount      	| Sets `Amount` data.                                                                                                             	|
| request     	| Calls `PaymentRequest` Zarinpal endpoint and returns a `RequestedPayment` object with available transaction ID and payment URL.<br>Might throw `GatewayException` if the request was rejected by Zarinpal. `GatewayException` is filled with user-friendly message that can be translated (see [Translations](#translations) section) and status code constants in `Evryn\LaravelToman\Gateways\Zarinpal\Status`.<br>Might throw an `InvalidConfigException` if gateway-specific configs are not set correctly (see [Config](#config) section above). 	|
 
 
## Payment Verification

In short, you can verify a payment callback with the following methods and catches:
```php
use Evryn\LaravelToman\Facades\PaymentVerification;
use Evryn\LaravelToman\Gateways\Zarinpal\Status;

// ...

try {
    $verifiedPayment = PaymentVerification::amount(1000)
        ->verify(request()); // or $request in your Controller
} catch (GatewayException $gatewayException) {
    if ($gatewayException->getCode() === Status::NOT_PAID) {
        // the payment has been cancelled
    } elseif ($gatewayException->getCode() === Status::ALREADY_VERIFIED) {
        // the payment has already been verified before
    }
} catch (InvalidConfigException $exception) {
    // Woops! wrong merchant_id or sandbox option is configurated.
}

// Use $verifiedPayment...
```

| Method      	| Description                                                                                                                     	|
|-------------	|---------------------------------------------------------------------------------------------------------------------------------	|
| amount      	| Sets `Amount` data. It should equal to the amount that payment request was created with.                                                                                                            	|
| verify     	| Calls `PaymentVerification` Zarinpal endpoint with callback queries gotten from request and returns a `VerifiedPayment` object with available reference ID.<br>Might throw a `GatewayException` if the request was rejected by Zarinpal. `GatewayException` is filled with user-friendly message that can be translated (see [Translations](#translations) section) and status code constants in `Evryn\LaravelToman\Gateways\Zarinpal\Status`.<br>Might throw an `InvalidConfigException` if gateway-specific configs are not set correctly (see [Config](#config) section above) 	|
 

## Translations

Currently, two `fa` (Persian) and `en` (English) languages are added to display payment status (in `GatewayException` especially).

See [Getting Started/Translations](../translations.md) to find out how to set application locale or customize these messages.
