<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Payment Gateway Name
    |--------------------------------------------------------------------------
    |
    | Here you may specify which of the payment gateway drivers below you wish
    | to use as your default payment gateway in order to request payment to be
    | paid and verify incoming webhook callbacks.
    |
    */

    'default' => env('PAYMENT_GATEWAY', 'zarinpal'),

    /*
    |--------------------------------------------------------------------------
    | Payment Gateways
    |--------------------------------------------------------------------------
    |
    | Here you may configure the payment gateways for your application. Note that
    | each payment gateway driver might have its own configuration set.
    */

    'gateways' => [

        'zarinpal' => [
            // Setting to true makes all payments happen in a testing environment to fake transactions.
            // Set it to false on production to receive real payments.
            'sandbox' => env('ZARINPAL_SANDBOX', false),

            // Merchant ID of your gateway provided by Zarinpal for your gateway
            // only. Looks like this: xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx
            'merchant_id' => env('ZARINPAL_MERCHANT_ID'),
        ],

        'idpay' => [
            // Setting to true makes all payments happen in a testing environment to fake transactions.
            // Set it to false on production to receive real payments.
            'sandbox' => env('IDPAY_SANDBOX', false),

            // API Key of your gateway provided by IDPay in your dashboard
            // only. Looks like this: xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx
            'api_key' => env('IDPAY_API_KEY'),
        ],

    ],

    /*
   |--------------------------------------------------------------------------
   | Default Currency
   |--------------------------------------------------------------------------
   |
     Gateways accept different currencies; So in order to avoid confusion
   | when switching between gateways, you can specify your intended currency
   | here, and Toman will convert to proper one automatically.
   | You can of course override it and specify another currency during making
   | requests using Money object too.
   |
   | Supported currencies: "toman", "rial"
   |
   */

    'currency' => 'toman',

    /*
    |--------------------------------------------------------------------------
    | Default Description
    |--------------------------------------------------------------------------
    |
    | Many gateways require a clear description of transaction purpose. You can
    | specify it here globally or simply override it by defining related data()
    | on Payment object.
    | Here, :amount will be replaced by actual payment amount value.
    |
    */

    'description' => 'پرداخت :amount در '.env('APP_NAME', 'اپلیکیشن'),

    /*
    |--------------------------------------------------------------------------
    | Default Callback Route
    |--------------------------------------------------------------------------
    |
    | Here you may specify which named route you want to use as default callback
    | URL. You can always override it with callback() method on Payment object.
    | For example, the route can be defined in routes/web.php:
    |   Route::get('callbacks/payment-verification')->name('payment.callback');
    |
    */

    // 'callback_route' => 'payment.callback',

];
