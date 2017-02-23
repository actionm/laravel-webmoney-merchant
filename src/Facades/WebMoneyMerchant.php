<?php

namespace ActionM\WebMoneyMerchant\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Actionm\WebMoneyMerchant\WebMoneyMerchant
 */
class WebMoneyMerchant extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'webmoneymerchant';
    }
}
