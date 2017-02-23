<?php

namespace ActionM\WebMoneyMerchant;

use Illuminate\Notifications\Notifiable as NotifiableTrait;

class WebMoneyMerchantNotifiable
{
    use NotifiableTrait;

    public function routeNotificationForMail()
    {
        return config('webmoney-merchant.mail.to');
    }

    public function routeNotificationForSlack()
    {
        return config('webmoney-merchant.slack.webhook_url');
    }

    /**
     * @return mixed
     */
    public function getKey()
    {
        return 1;
    }
}
