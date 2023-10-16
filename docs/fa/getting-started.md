> اگه نیاز شد، توی  [evryn/laravel-toman-example](https://github.com/evryn/laravel-toman-example) می‌تونین یه نمونه پروژه کامل با این پکیج رو ببینین.

## پیش‌نیازها

| پکیج | فریم‌ورک Laravel | PHP  | وضعیت |
| ------------- |:-------------:|:-----:| ---:|
| &lrm;3.\*      | &lrm;10.\*, &lrm;9.\* | &lrm;>= 8.0 | فعال 🚀 |
| &lrm;2.\*      | &lrm;9.\*, &lrm;8.\*, &lrm;7.\* | &lrm;>= 7.3 | |
| &lrm;1.\*      | &lrm;6.\*, &lrm;5.8.\*       |   &lrm;>= 7.2 |  |

## نصب

پکیج رو با Composer نصب کنین:
```bash
composer require evryn/laravel-toman
```

## تنظیمات
یه سری تنظیمات در نظر گرفتیم که باعث می‌شه کدهای کم‌تری استفاده کنین.

با دستور زیر، فایل کانفیگ پکیج رو منتشر کنین:
```bash
php artisan vendor:publish --provider="Evryn\LaravelToman\LaravelTomanServiceProvider" --tag=config
```

حالا می‌تونین تو فایل `config/toman.php` این تنظیمات رو انجام بدین.

## شخصی‌سازی متن‌ها (اختیاری)
درگاه‌ها ممکنه پاسخ رو در وضعیت‌های مختلفی برگردونن که هر کدوم معنی خاصی داره. تومن این متن‌ها رو هم نوشته تا کارتون راحت‌تر باشه.

با دستور زیر، فایل مربوط به متن‌ها رو منتشر کنین:
```bash
php artisan vendor:publish --provider="Evryn\LaravelToman\LaravelTomanServiceProvider" --tag=lang
```

فایل‌ها تو مسیر &lrm;`/resource/lang/vendor/toman` قابل ویرایش هستن.


## قدم بعدی
یکی از درگاه‌ها رو راه بندازین:
 * [💳 زرین‌پال](fa/gateways/zarinpal.md)
 * [💳 آی‌دی پِی](fa/gateways/idpay.md)


