# Translations

Gateway requests might result in different states and each of them is meaningful. This package also contains those messages so you don't need to write them again.

## Setting Locale

Usually `fa` (Persian) and `en` (English) translations are included with each gateway added.
All you need to do is to set your applications locale to one of these languages in `config/app.php` file:
```php
'locale' => 'fa',

// you may also set this too:
'fallback_locale' => 'en',
```

And of course, if you don't want to touch it, you can change it on-the-fly:
```php
App::setLocale('fa');
```

That's all! 

## Customizing Messages

Use following command to publish package translation files:
```bash
php artisan vendor:publish --provider="Evryn\LaravelToman\LaravelTomanServiceProvider" --tag=lang
```

Now translation files are ready to be modified at:

```
/resource/lang/vendor/toman
                        /fa
                            zarinpal.php
                            ...
                        /en
                            zarinpal.php
                            ...
```
