# Laravel payment processor package for WebMoney Merchant

[![Latest Stable Version](https://poser.pugx.org/actionm/laravel-webmoney-merchant/v/stable)](https://packagist.org/packages/actionm/laravel-webmoney-merchant)
[![Build Status](https://img.shields.io/travis/actionm/laravel-webmoney-merchant/master.svg?style=flat-square)](https://travis-ci.org/actionm/laravel-webmoney-merchant)
[![SensioLabsInsight](https://insight.sensiolabs.com/projects/3a9e6fd9-82d5-4efd-a8de-419d4b0c37d0/mini.png)](https://insight.sensiolabs.com/projects/3a9e6fd9-82d5-4efd-a8de-419d4b0c37d0)
[![Quality Score](https://img.shields.io/scrutinizer/g/actionm/laravel-webmoney-merchant.svg?style=flat-square)](https://scrutinizer-ci.com/g/actionm/laravel-webmoney-merchant)
[![Total Downloads](https://img.shields.io/packagist/dt/actionm/laravel-webmoney-merchant.svg?style=flat-square)](https://packagist.org/packages/actionm/laravel-webmoney-merchant)
[![License](https://poser.pugx.org/actionm/laravel-webmoney-merchant/license)](https://packagist.org/packages/actionm/laravel-webmoney-merchant)

Accept payments via WebMoney Merchant ([merchant.webmoney.ru](https://merchant.webmoney.ru/conf/default.asp)) using this Laravel framework package ([Laravel](https://laravel.com)).

- receive payments, adding just the two callbacks
- receive payment notifications via your email or Slack

You can accept payments with WebMoney Merchant via WebMoney, credit cards etc.

#### Laravel 5.3, 5.4, PHP >= 5.6.4

## Installation

You can install the package through Composer:

``` bash
composer require actionm/laravel-webmoney-merchant
```


Add the service provider to the `providers` array in `config/app.php`:

```php
'providers' => [

    ActionM\WebMoneyMerchant\WebMoneyMerchantServiceProvider::class,
    
]
```

Add the `WebMoneyMerchant` facade to your facades array:

```php
    'WebMoneyMerchant' => ActionM\WebMoneyMerchant\Facades\WebMoneyMerchant::class,
```

Publish the configuration file and views
``` bash
php artisan vendor:publish --provider="ActionM\WebMoneyMerchant\WebMoneyMerchantServiceProvider" 
```

Publish only the configuration file
``` bash
php artisan vendor:publish --provider="ActionM\WebMoneyMerchant\WebMoneyMerchantServiceProvider" --tag=config 
```

Publish only the views
``` bash
php artisan vendor:publish --provider="ActionM\WebMoneyMerchant\WebMoneyMerchantServiceProvider" --tag=views 
```

## Configuration

Once you have published the configuration files, please edit the config file in `config/webmoney-merchant.php`.

- Create an account on [merchant.webmoney.ru](http://merchant.webmoney.ru)
- Set your project settings:
  - Merchant name;
  - Secret Key;
  - Secret Key X20;
  - Result URL;
  - Control sign forming method = `SHA256`; 
  - Necessarily require signature payment form = `ON`; 
  - Process payments with unique only lmi_payment_no = `ON`;

- After the configuration has been published, edit `config/webmoney-merchant.php`
- Copy the `Secret Key X20` and `Secret Key` params and paste into `config/webmoney-merchant.php`
- Set the callback static function for `searchOrderFilter` and `paidOrderFilter`
- Set notification channels (email and/or Slack) and Slack `webhook_url` 
 
## Usage

1) Generate an HTML payment form with enabled payment methods:

``` php
$payment_amount = Order amount 

$payment_no = Unique order number in your project, numbers only from 1 to 2147483647

$item_name = Name of your order item, only latin characters.

```

``` php
WebMoneyMerchant::generatePaymentForm($payment_amount, $payment_no, $item_name);
```

Customize the HTML payment form in the published view:
 
`app/resources/views/vendor/webmoney-merchant/payment_form.blade.php`

2) Process the request from WebMoneyMerchant:
``` php
WebMoneyMerchant::payOrderFromGate(Request $request)
```
## Important

You must define callbacks in `config/webmoney-merchant.php` to search the order and save the paid order.


``` php
 'searchOrderFilter' => null  // ExampleController:searchOrderFilter($request)
```

``` php
 'paidOrderFilter' => null  // ExampleController::paidOrderFilter($request,$order)
```

## Example

The process scheme:

1. The request comes from `merchant.webmoney.ru` `GET` `http://yourproject.com/webmoney/result` to check if your website is available.
2. The request comes from `merchant.webmoney.ru` `POST` `http://yourproject.com/webmoney/result` (with params).
3. The function`ExampleController@payOrderFromGate` runs the validation process (auto-validation request params).
4. The static function `searchOrderFilter` will be called (see `config/webmoney-merchant.php` `searchOrderFilter`) to search the order by the unique id.
5. If the current order status is NOT `paid` in your database, the static function `paidOrderFilter` will be called (see `config/webmoney-merchant.php` `paidOrderFilter`).

Add the route to `routes/web.php`:
``` php
Route::post('/webmoney/result', 'ExampleController@payOrderFromGate');
Route::get('/webmoney/result',  'ExampleController@payOrderFromGateOK');
```

> **Note:**
don't forget to save your full route url (e.g. http://example.com/webmoney/result ) for your project on [merchant.webmoney.ru](merchant.webmoney.ru).

Create the following controller: `/app/Http/Controllers/ExampleController.php`:

``` php
class ExampleController extends Controller
{

    /**
     * Search the order if the request from WebMoney Merchant is received.
     * Return the order with required details for the webmoney request verification.
     *
     * @param Request $request
     * @param $order_id
     * @return mixed
     */
    public static function searchOrderFilter(Request $request, $order_id) {

        // If the order with the unique order ID exists in the database
        $order = Order::where('unique_id', $order_id)->first();

        if ($order) {
            $order['WEBMONEY_orderSum'] = $order->amount; // from your database

            // if the current_order is already paid in your database, return strict "paid"; 
            // if not, return something else
            $order['WEBMONEY_orderStatus'] = $order->order_status; // from your database
            return $order;
        }

        return false;
    }

    /**
     * When the payment of the order is received from WebMoney Merchant, you can process the paid order.
     * !Important: don't forget to set the order status as "paid" in your database.
     *
     * @param Request $request
     * @param $order
     * @return bool
     */
    public static function paidOrderFilter(Request $request, $order)
    {
        // Your code should be here:
        YourOrderController::saveOrderAsPaid($order);

        // Return TRUE if the order is saved as "paid" in the database or FALSE if some error occurs.
        // If you return FALSE, then you can repeat the failed paid requests on the WebMoney Merchant website manually.
        return true;
    }

    /**
     * Process the request from the WebMoney Merchant route.
     * searchOrderFilter is called to search the order.
     * If the order is paid for the first time, paidOrderFilter is called to set the order status.
     * If searchOrderFilter returns the "paid" order status, then paidOrderFilter will not be called.
     *
     * @param Request $request
     * @return mixed
     */
    public function payOrderFromGate(Request $request)
    {
        return WebMoneyMerchant::payOrderFromGate($request);
    }
    
    /**
    * Returns the service status for WebMoney Merchant request
    */
    public function payOrderFromGateOK()
    {
        return "YES";
    }
    
}
```


## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Testing

``` bash
$ composer test
```

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security

If you discover any security related issues, please send me an email at actionmanager@gmail.com instead of using the issue tracker.

## Credits

- [ActionM](https://github.com/actionm)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
