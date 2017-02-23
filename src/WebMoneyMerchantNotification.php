<?php

namespace ActionM\WebMoneyMerchant;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Messages\SlackMessage;
use Illuminate\Notifications\Messages\SlackAttachment;
use ActionM\WebMoneyMerchant\Events\WebMoneyMerchantEvent;

class WebMoneyMerchantNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /** @var \ActionM\WebMoneyMerchant\Events\WebMoneyMerchantEvent * */
    protected $event;

    public function via($notifiable)
    {
        return config('webmoney-merchant.channels');
    }

    public function setEvent(WebMoneyMerchantEvent $event)
    {
        $this->event = $event;

        return $this;
    }

    public function getEvent()
    {
        return $this->event;
    }

    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->error()
            ->subject('WebMoneyMerchant payment message from '.config('app.url'))
            ->line($this->event->title)
            ->line('IP: '.$this->event->ip)
            ->line("Request details: {$this->event->details}");
    }

    public function toSlack()
    {
        $slack_message = new SlackMessage();
        $slack_message->level = $this->event->type;

        return $slack_message
            ->content('WebMoneyMerchant payment message from '.config('app.url'))
            ->attachment(function (SlackAttachment $attachment) {
                $attachment->fields([
                    'Title' => $this->event->title,
                    'IP' => $this->event->ip,
                    'Request details' => $this->event->details,
                ]);
            });
    }
}
