# Using Zarinpal Gateway

Implementation of [Zarinpal.com](https://www.zarinpal.com) gateway is based on version 1.3 of their [official document](https://github.com/ZarinPal-Lab/Documentation-PaymentGateway/).

## Getting Started
### Setup

Zarinpal gateway requires the following variables in `.env` file to work:

| Environment Variable 	| Description                                                                                                                                                                                                                                        	|
|----------------------	|----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------	|
| `PAYMENT_GATEWAY` 	    | (**Required**)<br>Must equal `zarinpal` in order to use this gateway provider.                                                                            	|
| `ZARINPAL_MERCHANT_ID` 	| (**Required**)<br>Your gateway's merchant ID which can be gotten from your panel.<br>Example: `0bcf346fc-3a79-4b36-b936-5ccbc2be0696`                                                                                                             	|
| `ZARINPAL_SANDBOX`     	| (Optional. Default: `false`)<br>Set it to `true` in your development environment to make test calls in a simulated environment provided by the gateway without real payments.

Example:
```dotenv
...

PAYMENT_GATEWAY=zarinpal
ZARINPAL_MERCHANT_ID=0bcf346fc-3a79-4b36-b936-5ccbc2be0696
```

### Currency

You can specify your intended currency in a few ways: 

**Using config:**
 
| Method           | Config                     | Means |
|------------------|----------------------------|--------------|
| `amount(10000)` | `toman.currency = 'toman'` | 10,000 Toman   |
| `amount(10000)` | `toman.currency = 'rial'`  | 1,000 Toman    |

**Specifying explicitly:**
```php
use Evryn\LaravelToman\Money;

...->amount(Money::Rial(10000));
...->amount(Money::Toman(1000));
```

## ⚡ Request New Payment

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
| `amount($amount)`      	| **(Required)** Set amount for payment.                                                                                                             	|
| `callback($url)`    	| Set an absolute callback URL. Overrides `callback_route` config.                                  	|
| `description($string)` 	| Set description. Overrides `description` config.                                                                     	|
| `mobile($mobile)`      	| Set mobile.                                                                                                             	|
| `email($email)`       	| Set email.                                                                                                              	|
| `request()`     	| Request payment and return `RequestedPayment` object                                                                              |


Using returned `RequestedPayment`:

| <div style="width:200px">Method</div>             	| Description                                                                                                                     	|
|--------------------	|---------------------------------------------------------------------------------------------------------------------------------	|
| `successful()`    	| Payment request was successful, its transaction ID is available and can be redirected for payment.                                                                 	|
| `transactionId()`      	| <span class="green"><span class="green">[On Success]</span></span> Get transaction ID.                                                                                                             	|
| `pay($options = [])`      	| <span class="green">[On Success]</span> Redirect to payment URL from your controller. Returns a `RedirectResponse` object.<br>An option can be optionally passed in to explicitly select final gateway: `['gateway' => 'Sep']` . You can contact ZarinPal to make them available.                                                                                                            	|
| `paymentUrl($options = [])`       	| <span class="green">[On Success]</span> Get payment URL. `$options` is optional and same as above.                                                                                                            	|
| `failed()` 	| Payment request was failed and proper messages and exception are available.                                                                     	|
| `messages()`      	| <span class="red">[On Failure]</span> Get list of error messages.                                                                                                             	|
| `message()`     	| <span class="red">[On Failure]</span> Get first error message. |
| `throw()`     	| <span class="red">[On Failure]</span> Throw exception related to the failure. |

 
 
## ⚡ Verify Payment

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
        // Use $request->transactionId() to match the payment record stored
        // in your persistence database and get expected amount, which is required
        // for verification.

        $payment = $request->amount(1000)->verify();

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
| `amount($amount)`      	| **(Required)** Set amount that is expected to be paid.                                                                                                             	|
| `transactionId($id)`    	| Set transaction ID to verify. `CallbackRequest` sets it automatically.                                  	|
| `verify()`     	| Verify payment and return `CheckedPayment` object                                                                              |


Using returned `CheckedPayment`:

| Method             	| Description                                                                                                                     	|
|--------------------	|---------------------------------------------------------------------------------------------------------------------------------	|
| `transactionId()`      	| Get transaction ID.                                                                                                             	|
| `successful()`    	| Payment is newly verified and its reference ID is available.                                                                 	|
| `transactionId()`      	| <span class="green">[On Success]</span> Get reference ID.                                                                                                             	|
| `alreadyVerified()`    	| Payment was once verified before. Reference ID is available too.                                                                	|
| `failed()` 	| Payment was failed and proper messages and exception are available.                                                                     	|
| `messages()`      	| <span class="red">[On Failure]</span> Get list of error messages.                                                                                                             	|
| `message()`     	| <span class="red">[On Failure]</span> Get first error message. |
| `throw()`     	| <span class="red">[On Failure]</span> Throw exception related to the failure. |

<hr>

## More

### Manual Payment Verification
If you want to verify a payment manually (instead of using intended `CallbackRequest`), you just need to use `Toman` facade:
```php
use Evryn\LaravelToman\Facades\Toman;

// ...

$payment = Toman::transactionId('A00001234')
    ->amount(1000)
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

### Testing Zarinpal Gateway
If you're making automated tests for your application and want to see if you're interacting with Laravel Toman properly, go on.

#### 🧪 Test Payment Request 

Use `Toman::fakeRequest()` to stub request result and assert expected request data with `Toman::assertRequested()` method by a truth test.

```php
use Evryn\LaravelToman\Facades\Toman;
use Evryn\LaravelToman\Money;

final class PaymentTest extends TestCase
{
    /** @test */
    public function requests_new_payment_with_proper_data()
    {
        // Stub a successful or failed payment request result
        Toman::fakeRequest()->successful()->withTransactionId('A123');

        // Toman::fakeRequest()->failed();

        // Act with your app ...

        // Assert that you've correctly requested payment
        Toman::assertRequested(function ($request) {
            return $request->merchantId() === 'your-merchant-id'
                && $request->callback() === route('callback-route')
                && $request->amount()->is(Money::Toman(50000));
        });
    }
}
```

#### 🧪 Test Payment Verification 

Use `Toman::fakeVerification()` to stub verification result and assert its expected data with `Toman::assertCheckedForVerification()` method by a truth test.

```php
use Evryn\LaravelToman\Facades\Toman;
use Evryn\LaravelToman\Money;

final class PaymentTest extends TestCase
{
    /** @test */
    public function verifies_payment_with_proper_data()
    {
        // Stub a successful, already verified or failed payment verification result
        Toman::fakeVerification()
            ->successful()
            ->withTransactionId('A123')
            ->withReferenceId('R123');

        // Toman::fakeVerification()
        //     ->alreadyVerified()
        //     ->withTransactionId('A123')
        //     ->withReferenceId('R123');

        // Toman::fakeVerification()
        //     ->failed()
        //     ->withTransactionId('A123');

        // Act with your app ...

        // Assert that you've correctly verified payment
        Toman::assertCheckedForVerification(function ($request) {
            return $request->merchantId() === 'your-merchant-id'
                && $request->transactionId() === 'A123'
                && $request->amount()->is(Money::Toman(50000));
        });
    }
}
```
