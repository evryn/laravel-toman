# Mocking

If you're writing test suit for your own application, you don't need to test *how internally the package works* since it has already been tested in the package itself.

All you need to do is to test if you're calling right and expected methods on the package interface and since `PaymentRequest` and `PaymentVerification` are both built on Laravel [Facades](https://laravel.com/docs/master/facades), you can simply mock them.

For example, let say that you have following logic to make and verify payments:

```php
// routes/web.php

// Request new payment from gateway
Route::post('/new-payment', function (Request $request) {
    $requestedPayment = PaymentRequest::callback(URL::route('callback'))
        ->amount($request->amount)
        ->description('Your first payment')
        ->mobile('09350000000')
        ->email('amirreza@example.com')
        ->request();
        
    return $requestedPayment->pay();
});

// Verify payment after gateway redirection
Route::get('/payment-callback', function (Request $request) {
    // Get correct saved payment amount from your DB according to $request->Authority or similar callback data

    PaymentVerification::amount(1000)->verify($request);
})->name('callback');
```

## Testing PaymentRequest

For payment request route (`/new-payment`), we can write a Feature test:

```php
<?php
namespace Tests\Feature;

use Tests\TestCase;
use Evryn\LaravelToman\Facades\PaymentRequest;
use Evryn\LaravelToman\Results\RequestedPayment;
use Illuminate\Support\Facades\URL;

class PaymentTest extends TestCase
{
    // ...
    
    /** @test */
    public function requests_with_correct_details()
    {
        PaymentRequest::shouldReceive('callback')->once()->with(URL::route('callback'))->andReturnSelf();
        PaymentRequest::shouldReceive('description')->once()->with('Your first payment')->andReturnSelf();
        PaymentRequest::shouldReceive('mobile')->once()->with('09350000000')->andReturnSelf();
        PaymentRequest::shouldReceive('email')->once()->with('amirreza@example.com')->andReturnSelf();
        PaymentRequest::shouldReceive('amount')->once()->with(1000)->andReturnSelf();
        PaymentRequest::shouldReceive('request')->once()->withNoArgs()->andReturn(
            new RequestedPayment('000123', 'https://example.com/000123/pay')
        );

        $this->get('/new-payment')->assertRedirect('https://example.com/000123/pay');
    }

}
```

## Testing PaymentVerification

We can simulate callback request and write a Feature test for that:

```php
<?php
namespace Tests\Feature;

use Tests\TestCase;
use Evryn\LaravelToman\Facades\PaymentVerification;
use Evryn\LaravelToman\Results\VerifiedPayment;
use Illuminate\Http\Request;

class PaymentTest extends TestCase
{
    // ...
    
    /** @test */
    public function verifies_payment_after_being_redirected_to_callback_page()
    {
        PaymentVerification::shouldReceive('amount')->once()->with(1000)->andReturnSelf();
        PaymentVerification::shouldReceive('verify')->once()->with(\Mockery::type(Request::class))->andReturn(
            new VerifiedPayment('123456789')
        );

        // 'Authority' and 'Status' are part of Zarinpal callback data; see their docs.
        // We'll add fakers to detach this implementation details soon.
        $this->get('/payment-callback?Authority=1234')->assertOk();
    }

}
```

# Testing this Package

This step is not required to be done however, if you're willing to test this package itself, simply clone the package and go to cloned directory:
```bash
git clone git@github.com:evryn/laravel-toman.git
cd laravel-toman
```

Install dependencies:
```bash
composer self-update
composer update --prefer-stable --prefer-dist --no-interaction --no-suggest
```

There are some real integration tests in sandbox environment of gateways too. To test them, add a basic `phpunit.xml` and define your private real `ZARINPAL_MERCANT_ID` there.
Finally run tests:
```bash
composer test-dev
```

Coverage results will be available at `coverage` directory.

> See `.travis.yml` file to find out more how we run tests.