# استفاده از درگاه IDPay

تومن درگاه [IDPay.ir](https://idpay.ir) رو بر اساس نسخه 1.1 [داکیومنت رسمی‌شون](https://idpay.ir/web-service/v1.1/) ساخته.

## شروع به کار
### تنظیمات

درگاه آی‌دی پِی نیاز به تغییر این مقادیر تو فایل `.env` داره:

| متغیر محیطی 	| توضیحات   	|
|----------------------	|----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------	|
| `PAYMENT_GATEWAY` 	    | (**اجباری**)<br>باید `idpay` باشه.     	|
| `IDPAY_API_KEY` 	| (**اجباری**)<br>API Key درگاه پرداخت که می‌تونین از پنل ارائه‌دهنده بگیرین.<br>نمونه: `0bcf346fc-3a79-4b36-b936-5ccbc2be0696`    	|
| `IDPAY_SANDBOX`     	| (اختیاری. مقدار پیش‌فرض: `false`)<br>اگه تنظیم شه به `true`، همه درخواست‌ها تو محیط تست این درگاه بدون پرداخت واقعی انجام می‌شه. این شرایط تو محیط توسعه لوکال به درد می‌خوره.

نمونه:
```dotenv
...

PAYMENT_GATEWAY=idpay
IDPAY_API_KEY=0bcf346fc-3a79-4b36-b936-5ccbc2be0696
```

#### واحد پولی

واحد پولی رو می‌تونین به دو صورت معین کنین:

**استفاده از فایل تنظیمات (کانفیگ):**
 
| متد           | تنظیم                     | یعنی |
|------------------|----------------------------|--------------|
| `amount(10000)` | `toman.currency = 'toman'` | 10,000 تومان   |
| `amount(10000)` | `toman.currency = 'rial'`  | 1,000 تومان    |

**صریحاً مشخص کردن:**
```php
use Evryn\LaravelToman\Money;

...->amount(Money::Rial(10000));
...->amount(Money::Toman(1000));
```

## ⚡ درخواست پرداخت جدید

```php
use Evryn\LaravelToman\Facades\Toman;

// ...

$request = Toman::orderId('order_1500')
    ->amount(15000)
    // ->description('Subscribing to Plan A')
    // ->callback(route('payment.callback'))
    // ->mobile('09350000000')
    // ->email('amirreza@example.com')
    // ->name('Amirreza Nasiri')
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
| `amount($amount)`      	| **(الزامی)** تنظیم مبلغ قابل پرداخت.  	|
| `orderId($orderId)`      	| **(الزامی)** تنظیم شناسه سفارش. این شناسه یه رشته هست که سمت شما به صورت یکتا باید ساخته بشه و موقع تایید پرداخت ازش استفاده بشه.  	|
| `callback($url)`    	| تنظیم یک آدرس URL کامل به عنوان Callback URL. بر کانفیگ `callback_route` اولویت داره.   	|
| `description($string)` 	| تنظیم توضیحات پرداخت. بر کانفیگ `description` اولویت داره.    	|
| `mobile($mobile)`      	| تنظیم شماره موبایل پرداخت‌کننده.  	|
| `email($email)`       	| تنظیم ایمیل پرداخت‌کننده. |
| `name($name)`       	| تنظیم نام پرداخت‌کننده. |
| `request()`     	| ارسال درخواست پرداخت جدید و بازگردوندن یک آبجکت از نوع `RequestedPayment`. |


استفاده از `RequestedPayment` برگردونده شده:

| <div style="width:200px">متد</div>             	| توضیحات                	|
|--------------------	|---------------------------------------------------------------------------------------------------------------------------------	|
| `successful()`    	| درخواست پرداخت موفق بوده؛ شناسه تراکنش در دسترسه و می‌شه کاربر رو به صفحه پرداخت هدایت کرد.  	|
| `transactionId()`      	| <span class="green"><span class="green">[در صورت موفقیت]</span></span> دریافت شناسه تراکنش.                                                                                                             	|
| `pay($options = [])`      	| <span class="green">[در صورت موفقیت]</span> ریدایرکت کردن کاربر به صفحه پرداخت از کنترلر. یه آبجکت `RedirectResponse` برمی‌گردونه. |
| `paymentUrl($options = [])`       	| <span class="green">[در صورت موفقیت]</span> دریافت آدرس پراخت نهایی.                                                                                                            	|
| `failed()` 	|  درخواست پرداخت شکست خورد؛ پیام‌های مناسب و Exception در دسترس هستن.	|
| `messages()`      	| <span class="red">[در صورت شکست]</span> دریافت آرایه‌ای از پیام‌های خطا                                                                                                             	|
| `message()`     	| <span class="red">[در صورت شکست]</span> دریافت اولین پیام خطا. |
| `throw()`     	| <span class="red">[در صورت شکست]</span> پرت کردن Exception متناسب با خطا. |

 
 
## ⚡ تایید پرداخت

مکانیزم تایید پرداخت باید در مسیر مربوط به Callback ارسال شده پیاده شود. این کنترلر رو در نظر بگیرین:

```php
<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Evryn\LaravelToman\CallbackRequest;

class PaymentController extends Controller
{
    /**
    * Handle payment callback
    */
    public function callback(CallbackRequest $request)
    {
        // Use $request->transactionId() and $request->orderId() to match the 
        // non-paid payment. Take care of Double Spending. 

        $payment = $request->verify();

        if ($payment->successful()) {
            // Store the successful transaction details
            $referenceId = $payment->referenceId();
        }
        
        if ($payment->alreadyVerified()) {
            // ...
        }
        
        if ($payment->failed()) {
            // ...
        }
    }
}
```

متدهای قابل استفاده برای تایید پرداخت با `CallbackRequest` یا کلاس نمایه‌ای `Toman`:

| متد      	| توضیحات                                                                                                                     	|
|-------------	|---------------------------------------------------------------------------------------------------------------------------------	|
| `orderId($orderId)`      	| تنظیم شناسه سفارش. `CallbackRequest` اینو خودش پر می‌کنه. 	|
| `transactionId($id)`    	| تنظیم شناسه تراکنش برای بررسی تایید پرداخت. `CallbackRequest` اینو خودش پر می‌کنه.|
| `verify()`     	|ارسال درخواست بررسی و تایید پرداخت. یه آبجکت `CheckedPayment` برمی‌گردونه.  |


استفاده از `CheckedPayment` برگردونده شده:

| متد             	| توضیحات                                                                                                                     	|
|--------------------	|---------------------------------------------------------------------------------------------------------------------------------	|
| `orderId()`      	| دریافت شناسه سفارشی که تو درخواست ارسال شده بود.   	|
| `transactionId()`      	| دریافت شناسه تراکنشی که تو درخواست ارسال شده بود.   	|
| `successful()`    	| پرداخت موفق بوده و شناسه ارجاع در دسترسه.  	|
| `transactionId()`      	| <span class="green">[در صورت موفقیت]</span> دریافت شناسه ارجاع.              	|
| `alreadyVerified()`    	| پرداخت قبلاً یه بار بررسی و تایید شده بود. شناسه ارجاع همچنان در دسترسه.     	|
| `failed()` 	| پرداخت شکست خورده؛ پیام‌های مناسب و Exception در دسترس هستن. 	|
| `messages()`      	| <span class="red">[در صورت شکست]</span> دریافت آرایه‌ای از پیام‌های خطا                                                                                                             	|
| `message()`     	| <span class="red">[در صورت شکست]</span> دریافت اولین پیام خطا. |
| `throw()`     	| <span class="red">[در صورت شکست]</span> پرت کردن Exception متناسب با خطا. |

<hr>

## بیشتر

### تایید پرداخت به صورت دستی
اگه نیاز داشتین بدون استفاده از `CallbackRequest` تایید یه پرداخت رو بررسی کنین، می‌تونین از `Toman` استفاده کنین:

```php
use Evryn\LaravelToman\Facades\Toman;

// ...

$payment = Toman::transactionId('tid_123')
    ->orderId('order_1000')
    ->verify();

if ($payment->successful()) {
    // Store the successful transaction details
    $referenceId = $payment->referenceId();
}

if ($payment->alreadyVerified()) {
    // ...
}

if ($payment->failed()) {
    // ...
}
```

### تست کردن درگاه IDPay
اگه که برای نرم‌افزارتون تست سوئیت خودکار می‌نویسین و می‌خواین ببینین که با پکیج به درستی تعامل داره یا نه، ادامه بدین.

####  🧪 تست درخواست پرداخت

از `Toman::fakeRequest()` استفاده کنین تا یه نتیجه درخواست ایجاد پرداخت رو شبیه‌سازی کنین و بعد محتوای درخواست رو با `Toman::assertRequested()` مورد بررسی قرار بدین.

```php
use Evryn\LaravelToman\Facades\Toman;
use Evryn\LaravelToman\Money;

final class PaymentTest extends TestCase
{
    /** @test */
    public function requests_new_payment_with_proper_data()
    {
        // Stub a successful or failed payment request result
        Toman::fakeRequest()->successful()->withTransactionId('tid_123');

        // Toman::fakeRequest()->failed();

        // Act with your app ...

        // Assert that you've correctly requested payment
        Toman::assertRequested(function ($request) {
            return $request->merchantId() === 'your-idpay-api-key'
                && $request->callback() === route('callback-route')
                && $request->amount()->is(Money::Toman(50000));
        });
    }
}
```

####  🧪 تست بررسی و تایید پرداخت

از `Toman::fakeVerification()` استفاده کنین تا یه نتیجه درخواست بررسی و تایید رو شبیه‌سازی کنین و بعد محتوای درخواست رو با `Toman::assertCheckedForVerification()` مورد بررسی قرار بدین.

```php
use Evryn\LaravelToman\Facades\Toman;
use Evryn\LaravelToman\Money;

final class PaymentTest extends TestCase
{
    /** @test */
    public function verifies_payment_with_proper_data()
    {
        // Stub a successful, already verified or failed payment verification result
        Toman::fakeVerification()
            ->successful()
            ->withOrderId('order_100')
            ->withTransactionId('tid_123')
            ->withReferenceId('ref_123');

        // Toman::fakeVerification()
        //     ->alreadyVerified()
        //     ->withOrderId('order_100')
        //     ->withTransactionId('tid_123')
        //     ->withReferenceId('ref_123');

        // Toman::fakeVerification()
        //     ->failed()
        //     ->withOrderId('order_100')
        //     ->withTransactionId('tid_123');

        // Act with your app ...

        // Assert that you've correctly verified payment
        Toman::assertCheckedForVerification(function ($request) {
            return $request->merchantId() === 'your-idpay-api-id'
                && $request->orderId() === 'order_100'
                && $request->transactionId() === 'tid_123'
                && $request->amount()->is(Money::Toman(50000));
        });
    }
}
```
