# Translations

Gateway requests might result in different states, and each of them is meaningful. This package also contains those messages, so you don't need to write them again.

## Setting Locale

`fa` (Persian) and `en` (English) translations are included for each gateway. All you need to do is to set your applications locale to one of these languages in `config/app.php` file:
```php
'locale' => 'fa',

// you may also set this too:
'fallback_locale' => 'en',
```

## Customizing Messages

Use the following command to publish package translation files:
```bash
php artisan vendor:publish --provider=Evryn\LaravelToman\LaravelTomanServiceProvider --tag=lang
```

Now translation files are ready to be modified in `/resource/lang/vendor/toman`:
