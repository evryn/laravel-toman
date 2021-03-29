# Configuration

There are few configurable options to make your code more cleaner.

## Publish Config
Use the following command to publish package config:
```bash
php artisan vendor:publish --provider=Evryn\LaravelToman\LaravelTomanServiceProvider --tag=config
```

Now, a config file will be available to edit at `config/toman.php`.

## Package Options
 * `default`: 
 
It indicates your default payment gateway among available gateways in `gateways` option. By default, it'll be read from `TOMAN_GATEWAY` variable in your `.env` file.

 * `gateways`:  

It contains all configs related to gateways. See `Available Gateways` section to find out what options are available.
 
 * `description` (optional):  

It is the default description of the payment. `:amount` will be replaced by the actual payment amount. 

You can override it with `description()` method on `PaymentRequest`.

 * `callback_route` (optional):

It indicates the default payment callback route name.

Here is an example of defining a named route for callbacks in your `routes/web.php` file:
```php
Route::get('/payment-callback', 'PaymentController@callback')->name('payment.callback');
                                      [this can be placed in config] ↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑
```
