# Configuration

There are few configurable options to make you code more cleaner.

## Publish Config
Use following command to publish package config:
```bash
php artisan vendor:publish --provider="Evryn\LaravelToman\LaravelTomanServiceProvider" --tag=config
```

Now config file will be available at `config/toman.php`.

## Package Options
 * `default`: 
 
Indicates your default payment gateway among available gateways in `gateways` option. By default, it'll be read from `TOMAN_GATEWAY` variable in your `.env` file.

 * `gateways`:  

Contains all configs related to gateways. See `Available Gateways` section to find out what options are available.
 
 * `description` (optional):  

Most of available gateways require a description of payment upon new payment creation. Here, you can set default description message. `:amount` in the string will replaced by actual payment amount. 

You can override it with `description()` method on `PaymentRequest`.

 * `callback_route` (optional):

This is default callback *route name* for payment verification. Chaining `callback()` on `PaymentRequest` sets the final **URL** (not route name).

Here is an example of defining a named route for callbacks in your `routes/web.php` file:
```php
Route::get('/payment-callback', 'PaymentController@callback')->name('payment.callback');
                                      [this can be placed in config] ↑ 
```
 
 > Callbacks from gateways will hit via `GET` method. Be sure to define your callback route using `Route::get(...)`.
