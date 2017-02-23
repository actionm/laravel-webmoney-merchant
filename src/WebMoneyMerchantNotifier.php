<?php

namespace ActionM\WebMoneyMerchant;

use Illuminate\Contracts\Events\Dispatcher;
use ActionM\WebMoneyMerchant\Events\WebMoneyMerchantEvent;
use ActionM\WebMoneyMerchant\Exceptions\InvalidConfiguration;

class WebMoneyMerchantNotifier
{
    /**
     * register Notifier.
     */
    public function subscribe(Dispatcher $events)
    {
        // Listen events and send notification
        $events->listen(WebMoneyMerchantEvent::class, function ($event) {
            $event->type = str_replace('webmoneymerchant.', '', $event->type);

            if (! in_array($event->type, ['info', 'success', 'error'])) {
                $event->type = 'error';
            }

            $notifiable = app(config('webmoney-merchant.notifiable'));

            $notification = app(config('webmoney-merchant.notification'));
            $notification->setEvent($event);

            if (! $this->isValidNotificationClass($notification)) {
                throw InvalidConfiguration::notificationClassInvalid(get_class($notification));
            }

            if ($this->shouldSendNotification($notification)) {
                $notifiable->notify($notification);
            }
        });
    }

    public function isValidNotificationClass($notification)
    {
        if (get_class($notification) === WebMoneyMerchantNotification::class) {
            return true;
        }

        if (is_subclass_of($notification, WebMoneyMerchantNotification::class)) {
            return true;
        }

        return false;
    }

    public function shouldSendNotification($notification)
    {
        $callable = config('webmoney-merchant.notificationFilter');

        if (! is_callable($callable)) {
            return true;
        }

        return $callable($notification);
    }
}
