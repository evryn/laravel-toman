# Zarinpal Gateway

Implementation of [Zarinpal.com](https://www.zarinpal.com) gateway is based on version 1.3 of their [official document](https://github.com/ZarinPal-Lab/Documentation-PaymentGateway/).

## Serup

Zarinpal gateway requires the following variables in `.env` file to work:

| Environment Variable 	| Description                                                                                                                                                                                                                                        	|
|----------------------	|----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------	|
| TOMAN_GATEWAY 	    | (**Required**)<br>Must equal `zarinpal` in order to use this gateway provider.                                                                            	|
| ZARINPAL_MERCHANT_ID 	| (**Required**)<br>Your gateway's merchant ID which can be gotten from your Zarinpal panel.<br>Example: 0bcf346fc-3a79-4b36-b936-5ccbc2be0696                                                                                                             	|
| ZARINPAL_SANDBOX     	| (Optional. Default: false)<br>Set it to `true` in your development environment to make test calls in a simulated environment provided by Zarinpal without real payments.

Example:
```dotenv
...

TOMAN_GATEWAY=zarinpal
ZARINPAL_MERCHANT_ID=0bcf346fc-3a79-4b36-b936-5ccbc2be0696
```

## Request New Payment

```php
use Evryn\LaravelToman\Facades\Toman;

// ...

$request = Toman::amount(1000)
    // ->description('Subscribing to Plan A')
    // ->callback(route('payment.callback'))
    // ->mobile('09350000000')
    // ->email('amirreza@example.com')
    ->request();

if ($request->successful()) {
    $transactionId = $request->transactionId();
    // Store created transaction details for verification

    return $request->pay(); // Redirect to payment URL
}

if ($request->failed()) {
    // Handle transaction request failure; Probably showing proper error to user.
}
```

For requesting payment using `Toman` facade:

| Method      	| Description                                                                                                                     	|
|-------------	|---------------------------------------------------------------------------------------------------------------------------------	|
| amount(`$amount`)      	| **(Required)** Set amount for payment.                                                                                                             	|
| callback(`$url`)    	| Set an absolute callback URL. Overrides `callback_route` config.                                  	|
| description(`$string`) 	| Set description. Overrides `description` config.                                                                     	|
| mobile(`$mobile`)      	| Set mobile.                                                                                                             	|
| email(`$email`)       	| Set email.                                                                                                              	|
| request()     	| Request payment and return `RequestedPayment` object                                                                              |


Using returned `RequestedPayment`:

| <div style="width:200px">Method</div>             	| Description                                                                                                                     	|
|--------------------	|---------------------------------------------------------------------------------------------------------------------------------	|
| successful()    	| Payment request was successful, its transaction ID is available and can be redirected for payment.                                                                 	|
| transactionId()      	| <span class="green"><span class="green">[On Success]</span></span> Get transaction ID.                                                                                                             	|
| pay(`$options = []`)      	| <span class="green">[On Success]</span> Redirect to payment URL from your controller. Returns a `RedirectResponse` object.<br>An option can be optionally passed in to explicitly select final gateway: `['gateway' => 'Sep']` . You can contact ZarinPal to make them available.                                                                                                            	|
| paymentUrl(`$options = []`)       	| <span class="green">[On Success]</span> Get payment URL. `$options` is optional and same as above.                                                                                                            	|
| failed() 	| Payment request was failed and proper messages and exception are available.                                                                     	|
| messages()      	| <span class="red">[On Failure]</span> Get list of error messages.                                                                                                             	|
| message()     	| <span class="red">[On Failure]</span> Get first error message. |
| throw()     	| <span class="red">[On Failure]</span> Throw exception related to the failure. |

 
 
## Verify Payment

In short, you can verify a payment callback with the following methods and catches:

```php
use Evryn\LaravelToman\Facades\Toman;

// ...

$payment = Toman::amount(1000)
    // ->referenceId('A00001234')
    ->verify();

$transactionId = $payment->transactionId();

if ($payment->successful()) {
    $referenceId = $payment->referenceId();
    // Store a successful transaction details
}

if ($payment->alreadyVerified()) {
    // ...
}

if ($payment->failed()) {
    // ...
}
```

For requesting payment using `Toman` facade:

| Method      	| Description                                                                                                                     	|
|-------------	|---------------------------------------------------------------------------------------------------------------------------------	|
| amount(`$amount`)      	| **(Required)** Set amount that is expected to be paid.                                                                                                             	|
| transactionId(`$id`)    	| Set transaction ID to verify. If not, it'll extract from incoming callback request automatically.                                  	|
| verify()     	| Verify payment and return `CheckedPayment` object                                                                              |


Using returned `CheckedPayment`:

| Method             	| Description                                                                                                                     	|
|--------------------	|---------------------------------------------------------------------------------------------------------------------------------	|
| transactionId()      	| Get transaction ID.                                                                                                             	|
| successful()    	| Payment is newly verified and its reference ID is available.                                                                 	|
| transactionId()      	| <span class="green">[On Success]</span> Get reference ID.                                                                                                             	|
| alreadyVerified()    	| Payment was once verified before.                                                                 	|
| failed() 	| Payment was failed and proper messages and exception are available.                                                                     	|
| messages()      	| <span class="red">[On Failure]</span> Get list of error messages.                                                                                                             	|
| message()     	| <span class="red">[On Failure]</span> Get first error message. |
| throw()     	| <span class="red">[On Failure]</span> Throw exception related to the failure. |
