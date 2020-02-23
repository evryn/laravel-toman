> There is an example project using Laravel Toman you can find at [evryn/laravel-toman-example](https://github.com/evryn/laravel-toman-example). It contains payment code and few key tests.

# Requirements

Every released build is tested against combination of following config:
 * PHP: 7.2+
 * Laravel: 5.8+
 
 If you're not using this config, you should really take a look at lifetime support of Laravel and PHP.

# Installation

> ⚠️ Note: You're about to install a version of package that does not support latest version of Laravel. If you're using Laravel 5.4 to 5.7, continue reading. If you're using newer version, see master branch for latest stable version. 

Install package using composer:
```bash
composer require evryn/laravel-toman:laravel-5.4-compatible
```

# Setup

Specify default gateway and gateway-specific options in your `.env` file. For example if you're using Zarinpal gateway:
```bash
...

TOMAN_GATEWAY=zarinpal
ZARINPAL_MERCHANT_ID=xxxx-xxxx-xxxx-xxxx
```

> See `Available Gateways` section to find gateway-specific settings.

# Create new Payment

First, request a new payment from your gateway in your controller:
```php
use Evryn\LaravelToman\Facades\PaymentRequest;

// ...

$requestedPayment = PaymentRequest::callback('...') // Set verification callback URL
    ->amount(2000)  // Set amount to pay
    ->request();

// Save transaction ID and amount for verification purpose
$transactionId = $requestedPayment->getTransactionId();

// Redirect user to payment page in your controller method
return $requestedPayment->pay();
```

> See `Available Gateways` section for available or required PaymentRequest methods for your gateway.

# Verify It

Now that user is paid (or rejected) transaction, you need to verify it in the callback URL specified for the request:
```php

use Evryn\LaravelToman\Facades\PaymentVerification;

// ...

// Set amount that user had to pay (required my some gateways)
$verifiedPayment = PaymentVerification::amount(2000)->verify( request() );

// Get reference ID returned from the gateway. You may save and display it to user.
$referenceId = $verifiedPayment->getReferenceId();
```

> See `Available Gateways` section for available or required PaymentVerification methods for your gateway.
