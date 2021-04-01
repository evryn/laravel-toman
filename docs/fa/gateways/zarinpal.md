# استفاده از درگاه زرین‌پال

تومن درگاه [Zarinpal.com](https://www.zarinpal.com) رو بر اساس نسخه 1.3 [داکیومنت رسمی‌شون](https://github.com/ZarinPal-Lab/Documentation-PaymentGateway/) ساخته.

## تنظیمات

درگاه زرین‌پال نیاز به تغییر این مقادیر تو فایل &lrm;`.env` داره:

| متغیر محیطی 	| توضیحات   	|
|----------------------	|----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------	|
| TOMAN_GATEWAY 	    | (**اجباری**)<br>باید `zarinpal` باشه.     	|
| ZARINPAL_MERCHANT_ID 	| (**اجباری**)<br>کد درگاه پرداخت که می‌تونین از پنل زرین‌پال بگیرین.<br>نمونه: 0bcf346fc-3a79-4b36-b936-5ccbc2be0696    	|
| ZARINPAL_SANDBOX     	| (اختیاری. مقدار پیش‌فرض: false)<br>اگه تنظیم شه به `true`، همه درخواست‌ها تو محیط تست زرین‌پال بدون پرداخت واقعی انجام می‌شه. این شرایط تو محیط توسعه لوکال به درد می‌خوره.

نمونه:
```dotenv
...

TOMAN_GATEWAY=zarinpal
ZARINPAL_MERCHANT_ID=0bcf346fc-3a79-4b36-b936-5ccbc2be0696
```

## درخواست پرداخت جدید

```php
use Evryn\LaravelToman\Facades\Toman;

// ...

$request = Toman::amount(1000)
    // ->description('Subscribing to Plan A')
    // ->callback(route('payment.callback'))
    // ->mobile('09350000000')
    // ->email('amirreza@example.com')
    ->request();

if ($request->successful()) {
    $transactionId = $request->transactionId();
    // Store created transaction details for verification

    return $request->pay(); // Redirect to payment URL
}

if ($request->failed()) {
    // Handle transaction request failure; Probably showing proper error to user.
}
```

متدهای قابل استفاده برای ایجاد درخواست پرداخت جدید با کلاس نمایه‌ای `Toman`:

| متد      	| توضیحات  	|
|-------------	|---------------------------------------------------------------------------------------------------------------------------------	|
| &lrm;amount(`$amount`)      	| **(الزامی)** تنظیم مبلغ قابل پرداخت.  	|
| &lrm;callback(`$url`)    	| تنظیم یک آدرس URL کامل به عنوان Callback URL. بر کانفیگ `callback_route` اولویت داره.   	|
| &lrm;description(`$string`) 	| تنظیم توضیحات پرداخت. بر کانفیگ `description` اولویت داره.    	|
| &lrm;mobile(`$mobile`)      	| تنظیم شماره موبایل پرداخت‌کننده.  	|
| &lrm;email(`$email`)       	| تنظیم ایمیل پرداخت‌کننده. |
| &lrm;request()     	| ارسال درخواست پرداخت جدید و بازگردوندن یک آبجکت از نوع `RequestedPayment`. |


استفاده از `RequestedPayment` برگردونده شده:

| <div style="width:200px">متد</div>             	| توضیحات                	|
|--------------------	|---------------------------------------------------------------------------------------------------------------------------------	|
| successful()    	| درخواست پرداخت موفق بوده؛ شناسه تراکنش در دسترسه و می‌شه کاربر رو به صفحه پرداخت هدایت کرد.  	|
| transactionId()      	| <span class="green"><span class="green">[در صورت موفقیت]</span></span> دریافت شناسه تراکنش.                                                                                                             	|
| pay(`$options = []`)      	| <span class="green">[در صورت موفقیت]</span> ریدایرکت کردن کاربر به صفحه پرداخت از کنترلر. یه آبجکت `RedirectResponse` برمی‌گردونه.<br>یه آپشن اختیاری هم می‌شه برای ارسال کرد که مشخص کننده درگاه بانکی خاص برای پرداخت هست: `['gateway' => 'Sep']`. برای استفاده، باید با زرین‌پال صحبت کنین. |
| paymentUrl(`$options = []`)       	| <span class="green">[در صورت موفقیت]</span> دریافت آدرس پراخت نهایی. آپشن اختیاریش هم مثل مورد بالا هست.                                                                                                            	|
| failed() 	|  درخواست پرداخت شکست خورد؛ پیام‌های مناسب و Exception در دسترس هستن.	|
| messages()      	| <span class="red">[در صورت شکست]</span> دریافت آرایه‌ای از پیام‌های خطا                                                                                                             	|
| message()     	| <span class="red">[در صورت شکست]</span> دریافت اولین پیام خطا. |
| throw()     	| <span class="red">[در صورت شکست]</span> پرت کردن Exception متناسب با خطا. |

 
 
## تایید پرداخت

مکانیزم تایید پرداخت باید در مسیر مربوط به Callback ارسال شده پیاده شود.

```php
use Evryn\LaravelToman\Facades\Toman;

// ...

$payment = Toman::amount(1000)
    // ->referenceId('A00001234')
    ->verify();

$transactionId = $payment->transactionId();

if ($payment->successful()) {
    $referenceId = $payment->referenceId();
    // Store a successful transaction details
}

if ($payment->alreadyVerified()) {
    // ...
}

if ($payment->failed()) {
    // ...
}
```

متدهای قابل استفاده برای تایید پرداخت با کلاس نمایه‌ای `Toman`:

| Method      	| Description                                                                                                                     	|
|-------------	|---------------------------------------------------------------------------------------------------------------------------------	|
| amount(`$amount`)      	| **(اجباری)** تنظیم مبلغی که کاربر باید پرداخت کرده باشد. 	|
| transactionId(`$id`)    	| تنظیم شناسه تراکنش برای بررسی تایید پرداخت. به‌صورت پیش‌فرض، خودکار از درخواست ورودی استخراج می‌شه.|
| verify()     	|ارسال درخواست بررسی و تایید پرداخت. یه آبجکت `CheckedPayment` برمی‌گردونه.  |


استفاده از `CheckedPayment` برگردونده شده:

| Method             	| Description                                                                                                                     	|
|--------------------	|---------------------------------------------------------------------------------------------------------------------------------	|
| transactionId()      	| دریافت شناسه تراکنشی که تو درخواست ارسال شده بود.   	|
| successful()    	| پرداخت موفق بوده و شناسه ارجاع در دسترسه.  	|
| transactionId()      	| <span class="green">[در صورت موفقیت]</span> دریافت شناسه ارجاع.              	|
| alreadyVerified()    	| پرداخت قبلاً یه بار بررسی و تایید شده بود. شناسه ارجاع همچنان در دسترسه.     	|
| failed() 	| پرداخت شکست خورده؛ پیام‌های مناسب و Exception در دسترس هستن. 	|
| messages()      	| <span class="red">[در صورت شکست]</span> دریافت آرایه‌ای از پیام‌های خطا                                                                                                             	|
| message()     	| <span class="red">[در صورت شکست]</span> دریافت اولین پیام خطا. |
| throw()     	| <span class="red">[در صورت شکست]</span> پرت کردن Exception متناسب با خطا. |

<hr>

# تست کردن درگاه زرین‌پال
اگه که برای نرم‌افزارتون تست سوئیت خودکار می‌نویسین و می‌خواین ببینین که با پکیج به درستی تعامل داره یا نه، ادامه بدین.

## 🧪 تست درخواست پرداخت

از &lrm;`Toman::fakeRequest()` استفاده کنین تا یه نتیجه درخواست ایجاد پرداخت رو شبیه‌سازی کنین و بعد محتوای درخواست رو با &lrm;`Toman::assertRequested()` مورد بررسی قرار بدین.

```php
use Evryn\LaravelToman\Facades\Toman;

final class PaymentTest extends TestCase
{
    /** @test */
    public function requests_new_payment_with_proper_data()
    {
        // Stub a successful or failed payment request result
        Toman::fakeRequest()->successful()->withTransactionId('A123');
        // Toman::fakeRequest()->failed();

        // Act with your app ...

        // Assert that you've correctly requested payment
        Toman::assertRequested(function ($request) {
            return $request->merchantId() === 'your-merchant-id'
                && $request->callback() === route('callback-route')
                && $request->amount() === 50000;
        });
    }
}
```

## 🧪 تست بررسی و تایید پرداخت

از &lrm;`Toman::fakeVerification()` استفاده کنین تا یه نتیجه درخواست بررسی و تایید رو شبیه‌سازی کنین و بعد محتوای درخواست رو با &lrm;`Toman::assertCheckedForVerification()` مورد بررسی قرار بدین.

```php
use Evryn\LaravelToman\Facades\Toman;

final class PaymentTest extends TestCase
{
    /** @test */
    public function verifies_payment_with_proper_data()
    {
        // Stub a successful, already verified or failed payment verification result
        Toman::fakeVerification()
            ->successful()
            ->withTransactionId('A123')
            ->withReferenceId('R123');
        // Toman::fakeVerification()
        //     ->alreadyVerified()
        //     ->withTransactionId('A123')
        //     ->withReferenceId('R123');
        // Toman::fakeVerification()
        //     ->failed()
        //     ->withTransactionId('A123');

        // Act with your app ...

        // Assert that you've correctly verified payment
        Toman::assertCheckedForVerification(function ($request) {
            return $request->merchantId() === 'your-merchant-id'
                && $request->transactionId() === 'A123'
                && $request->amount() === 50000;
        });
    }
}
```
