<?php

namespace ActionM\WebMoneyMerchant\Exceptions;

use Exception;
use Illuminate\Notifications\Notification;

class InvalidConfiguration extends Exception
{
    public static function notificationClassInvalid($className)
    {
        return new self("Class {$className} is an invalid notification class. ".
            'A notification class must extend '.Notification::class);
    }

    public static function searchOrderFilterInvalid()
    {
        return new self('WebMoneyMerchant config: searchOrderFilter callback not set');
    }

    public static function orderPaidFilterInvalid()
    {
        return new self('WebMoneyMerchant config: paidOrderFilter callback not set');
    }

    public static function generatePaymentFormOrderParamsNotSet($field)
    {
        return new self('WebMoneyMerchant config: generatePaymentForm required order params not set ( field: `'.$field.'`)');
    }

    public static function generatePaymentFormOrderInvalidPaymentNo($field)
    {
        return new self('WebMoneyMerchant config: generatePaymentForm required order params not set ( field: `'.$field.'`)');
    }
}
