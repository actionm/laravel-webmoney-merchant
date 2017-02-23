<?php

return [

    /*
     * https://merchant.webmoney.ru/conf/guide.asp
     */

    /*
     * merchant.webmoney.ru LMI_PAYEE_PURSE = your purse: Z123456789012
     */
    'WM_LMI_PAYEE_PURSE' => env('WM_LMI_PAYEE_PURSE', ''),

    /*
     * merchant.webmoney.ru LMI_SECRET_X2
     */
    'WM_LMI_SECRET_X20'=> env('WM_LMI_SECRET_X20', ''),

    /*
     * merchant.webmoney.ru LMI_SECRET_KEY
     */
    'WM_LMI_SECRET_KEY' => env('WM_LMI_SECRET_KEY', ''),

    /*
     * Locale for the payment form
     */
    'locale' => 'ru-RU',  // ru-RU || en-US

    /*
     *  SearchOrderFilter
     *  Searches the order in the database and returns order details
     *  Must return the array with:
     *
     *  orderStatus
     *  orderCurrency
     *  orderSum
     */
    'searchOrderFilter' => null, //  'App\Http\Controllers\ExampleController::searchOrderFilter',

    /*
     *  PaidOrderFilter
     *  If current 'orderStatus' from DB != 'paid' then PaidOrderFilter will be called
     *  Update the order into the DB & other actions
     */
    'paidOrderFilter' => null, //  'App\Http\Controllers\ExampleController::paidOrderFilter',

    'payment_forms' => [
        'cards' => true,
        'sberbank' => true,
        'online_banking' => true,
        'mobile' => true,
        'webmoney' => true,
    ],

    // Allowed ip's
    'allowed_ips' => [
        '*',
    ],

    /*
     * The notification that will be sent after the payment request has been received.
     */
    'notification' => \ActionM\WebMoneyMerchant\WebMoneyMerchantNotification::class,

    /*
     * The notifiable to which the notification will be sent. The default
     * notifiable will use the mail and slack configuration specified
     * in this config file.
     */
    'notifiable' => \ActionM\WebMoneyMerchant\WebMoneyMerchantNotifiable::class,

    /*
     * By default notifications are always sent. You can pass a callable to filter
     * out certain notifications. The given callable will receive the notification. If the callable
     * returns false, the notification will not be sent.
     */
    'notificationFilter' => null,

    /*
     * The channels to which the notification will be sent.
     */
    'channels' => ['mail', 'slack'],

    'mail' => [
        'to' => '',  // your email
    ],

    'slack' => [
        'webhook_url' => '', // slack web hook to send notifications
    ],
];
