# Requirements

| Package | Laravel Framework | PHP  | Status |
| ------------- |:-------------:|:-----:| ---:|
| v2      | 8.x, 7.x | >= 7.3 | Active 🚀 |
| v1      | 6.x, 5.8.x       |   >= 7.2 |  |

# Installation

Install package using composer:
```bash
composer require "evryn/laravel-toman":"^1.1"
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
