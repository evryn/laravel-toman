# Using IDPay Gateway

Implementation of [IDPay.ir](https://idpay.ir) gateway is based on version 1.1 of their [official document](https://idpay.ir/web-service/v1.1/).

## Setup

IDPay gateway requires the following variables in `.env` file to work:

| Environment Variable 	| Description                                                                                                                                                                                                                                        	|
|----------------------	|----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------	|
| TOMAN_GATEWAY 	    | (**Required**)<br>Must equal `idpay` in order to use this gateway provider.                                                                            	|
| IDPAY_API_KEY 	| (**Required**)<br>Your gateway's API Key which can be gotten from your panel.<br>Example: 0bcf346fc-3a79-4b36-b936-5ccbc2be0696                                                                                                             	|
| IDPAY_SANDBOX     	| (Optional. Default: false)<br>Set it to `true` in your development environment to make test calls in a simulated environment provided by the gateway without real payments.

Example:
```dotenv
...

TOMAN_GATEWAY=idpay
IDPAY_API_KEY=0bcf346fc-3a79-4b36-b936-5ccbc2be0696
```

## Request New Payment

```php
use Evryn\LaravelToman\Facades\Toman;

// ...

$request = Toman::orderId('order_1500')
    ->amount(15000)
    // ->description('Subscribing to Plan A')
    // ->callback(route('payment.callback'))
    // ->mobile('09350000000')
    // ->email('amirreza@example.com')
    // ->name('Amirreza Nasiri')
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
| amount(`$amount`)      	| **(Required)** Set amount for payment. This gateway expects IRR unit.                                                                                                             	|
| orderId(`$orderId`)      	| **(Required)** Set order ID for payment. Order ID is a unique your-side generated string that will be used for verification.                                                                                                             	|
| callback(`$url`)    	| Set an absolute callback URL. Overrides `callback_route` config.                                  	|
| description(`$string`) 	| Set description. Overrides `description` config.                                                                     	|
| mobile(`$mobile`)      	| Set mobile.                                                                                                             	|
| email(`$email`)       	| Set email.                                                                                                              	|
| name(`$name`)       	| Set payer name.                                                                                                              	|
| request()     	| Request payment and return `RequestedPayment` object                                                                              |


Using returned `RequestedPayment`:

| <div style="width:200px">Method</div>             	| Description                                                                                                                     	|
|--------------------	|---------------------------------------------------------------------------------------------------------------------------------	|
| successful()    	| Payment request was successful, its transaction ID is available and can be redirected for payment.                                                                 	|
| transactionId()      	| <span class="green"><span class="green">[On Success]</span></span> Get transaction ID.                                                                                                             	|
| pay()      	| <span class="green">[On Success]</span> Redirect to payment URL from your controller. Returns a `RedirectResponse` object.                                                                                                            	|
| paymentUrl()       	| <span class="green">[On Success]</span> Get payment URL.                                                                                                            	|
| failed() 	| Payment request was failed and proper messages and exception are available.                                                                     	|
| messages()      	| <span class="red">[On Failure]</span> Get list of error messages.                                                                                                             	|
| message()     	| <span class="red">[On Failure]</span> Get first error message. |
| throw()     	| <span class="red">[On Failure]</span> Throw exception related to the failure. |

 
 
## Verify Payment

Verification must be implemented in the callback route. Consider the following controller method:

```php
<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Evryn\LaravelToman\CallbackRequest;

class PaymentController extends Controller
{
    /**
    * Handle payment callback
    */
    public function callback(CallbackRequest $request)
    {
        // Use $request->transactionId() and $request->orderId() to match the 
        // non-paid payment. Take care of Double Spending. 

        $payment = $request->verify();

        if ($payment->successful()) {
            // Store the successful transaction details
            $referenceId = $payment->referenceId();
        }
        
        if ($payment->alreadyVerified()) {
            // ...
        }
        
        if ($payment->failed()) {
            // ...
        }
    }
}
```

For requesting payment using `CallbackRequest` or `Toman` facade:

| Method      	| Description                                                                                                                     	|
|-------------	|---------------------------------------------------------------------------------------------------------------------------------	|
| orderId(`$orderId`)      	| Set the order ID you've generated when requesting the payment. `CallbackRequest` sets it automatically.                                                                                                              	|
| transactionId(`$id`)    	| Set transaction ID to verify. `CallbackRequest` sets it automatically.                                  	|
| verify()     	| Verify payment and return `CheckedPayment` object                                                                              |


Using returned `CheckedPayment`:

| Method             	| Description                                                                                                                     	|
|--------------------	|---------------------------------------------------------------------------------------------------------------------------------	|
| orderId()      	| Get order ID.                                                                                                             	|
| transactionId()      	| Get transaction ID.                                                                                                             	|
| successful()    	| Payment is newly verified and its reference ID is available.                                                                 	|
| transactionId()      	| <span class="green">[On Success]</span> Get reference ID.                                                                                                             	|
| alreadyVerified()    	| Payment was once verified before. Reference ID is available too.                                                                	|
| failed() 	| Payment was failed and proper messages and exception are available.                                                                     	|
| messages()      	| <span class="red">[On Failure]</span> Get list of error messages.                                                                                                             	|
| message()     	| <span class="red">[On Failure]</span> Get first error message. |
| throw()     	| <span class="red">[On Failure]</span> Throw exception related to the failure. |

<hr>

## More

### Manual Payment Verification
If you want to verify a payment manually (instead of using intended `CallbackRequest`), you just need to use `Toman` facade:
```php
use Evryn\LaravelToman\Facades\Toman;

// ...

$payment = Toman::transactionId('tid_123')
    ->orderId('order_1000')
    ->verify();

if ($payment->successful()) {
    // Store the successful transaction details
    $referenceId = $payment->referenceId();
}

if ($payment->alreadyVerified()) {
    // ...
}

if ($payment->failed()) {
    // ...
}
```

### 🧪 Testing IDPay Gateway
If you're making automated tests for your application and want to see if you're interacting with Laravel Toman properly, go on.

#### Test Payment Request 

Use `Toman::fakeRequest()` to stub request result and assert expected request data with `Toman::assertRequested()` method by a truth test.

```php
use Evryn\LaravelToman\Facades\Toman;

final class PaymentTest extends TestCase
{
    /** @test */
    public function requests_new_payment_with_proper_data()
    {
        // Stub a successful or failed payment request result
        Toman::fakeRequest()->successful()->withTransactionId('tid_123');
        // Toman::fakeRequest()->failed();

        // Act with your app ...

        // Assert that you've correctly requested payment
        Toman::assertRequested(function ($request) {
            return $request->merchantId() === 'your-idpay-api-key'
                && $request->callback() === route('callback-route')
                && $request->amount() === 50000;
        });
    }
}
```

#### Test Payment Verification 

Use `Toman::fakeVerification()` to stub verification result and assert its expected data with `Toman::assertCheckedForVerification()` method by a truth test.

```php
use Evryn\LaravelToman\Facades\Toman;

final class PaymentTest extends TestCase
{
    /** @test */
    public function verifies_payment_with_proper_data()
    {
        // Stub a successful, already verified or failed payment verification result
        Toman::fakeVerification()
            ->successful()
            ->withOrderId('order_100')
            ->withTransactionId('tid_123')
            ->withReferenceId('ref_123');
        // Toman::fakeVerification()
        //     ->alreadyVerified()
        //     ->withOrderId('order_100')
        //     ->withTransactionId('tid_123')
        //     ->withReferenceId('ref_123');
        // Toman::fakeVerification()
        //     ->failed()
        //     ->withOrderId('order_100')
        //     ->withTransactionId('tid_123');

        // Act with your app ...

        // Assert that you've correctly verified payment
        Toman::assertCheckedForVerification(function ($request) {
            return $request->merchantId() === 'your-idpay-api-id'
                && $request->orderId() === 'order_100'
                && $request->transactionId() === 'tid_123'
                && $request->amount() === 50000;
        });
    }
}
```
