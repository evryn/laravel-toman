> There is an example project using Laravel Toman you can find at [evryn/laravel-toman-example](https://github.com/evryn/laravel-toman-example). It contains a payment implementation and few critical tests.

## Requirements

| Package | Laravel Framework | PHP  | Status |
| ------------- |:-------------:|:-----:| ---:|
| 2.x      | 8.x, 7.x | >= 7.3 | Active 🚀 |
| 1.x      | 6.x, 5.8.x       |   >= 7.2 |  |

## Installation

Install the package using Composer:
```bash
composer require evryn/laravel-toman
```

## Configuration

There are few configurable options to make your code cleaner.

Use the following command to publish package config:
```bash
php artisan vendor:publish --provider="Evryn\LaravelToman\LaravelTomanServiceProvider" --tag=config
```

Now, a config file will be available to edit at `config/toman.php`. See available options there.

## Customizing Messages (Optional)

Gateways requests might result in different states, and each of them is meaningful. This package also contains those messages, so you don't need to write them again.

Use the following command to publish package translation files:
```bash
php artisan vendor:publish --provider="Evryn\LaravelToman\LaravelTomanServiceProvider" --tag=lang
```

Now, translation files are ready to be modified in `/resource/lang/vendor/toman`.

## Next Step
See how to use a gateway:
 * [💳 Zarinpal](gateways/zarinpal.md)
 * [💳 IDPay](gateways/idpay.md)
