<?php

namespace ActionM\WebMoneyMerchant\Test;

use Illuminate\Http\Request;
use ActionM\WebMoneyMerchant\Test\Dummy\Order;
use ActionM\WebMoneyMerchant\WebMoneyMerchantNotifiable;
use ActionM\WebMoneyMerchant\Events\WebMoneyMerchantEvent;
use ActionM\WebMoneyMerchant\Test\Dummy\AnotherNotifiable;
use ActionM\WebMoneyMerchant\WebMoneyMerchantNotification;
use ActionM\WebMoneyMerchant\Test\Dummy\AnotherNotification;
use Illuminate\Support\Facades\Notification as NotificationFacade;

class WebMoneyMerchantTest extends TestCase
{
    /** @test */
    public function test_env()
    {
        $this->assertEquals('testing', $this->app['env']);
    }

    /**
     * Send event with event_type.
     * @param $event_type
     * @return array|null
     */
    protected function fireEvent($event_type)
    {
        return event(
            new WebMoneyMerchantEvent(
                $event_type, ['title' => 'WebMoneyMerchant: notification', 'ip' => '127.0.0.1', 'request' => ['test' => 'test']]
            )
        );
    }

    /**
     * Create test request with custom method and add signature.
     * @param string $method
     * @param bool $signature
     * @return Request
     */
    protected function create_test_request($signature = false)
    {
        $params = [
            'LMI_PAYEE_PURSE' => '1',
            'LMI_PAYMENT_AMOUNT' => '1',
            'LMI_PAYMENT_NO' => '1',
            'LMI_MODE' => '0',
            'LMI_SYS_INVS_NO' => '1',
            'LMI_SYS_TRANS_NO' => '1',
            'LMI_SYS_TRANS_DATE' => '1',
            'LMI_PAYER_WM' => '1',
            'LMI_HASH' => '1',
            'LMI_HASH2' => '1',
            'LMI_PAYER_IP' => '1',
        ];

        if ($signature === false) {
            $params['LMI_HASH'] = $this->webmoneymerchant->getSignature(new Request($params));
        } else {
            $params['LMI_HASH'] = $signature;
        }

        $request = new Request($params);

        return $request;
    }

    /* always public for callback test */
    public function returnsFalseWhenTypeIsEmpty($notification)
    {
        return false;
    }

    /* always public for callback test */
    public function returnsTrueWhenTypeIsNotEmpty($notification)
    {
        $type = $notification->getEvent()->type;

        return ! empty($type);
    }

    /** @test */
    public function it_can_send_notification_when_payment_error()
    {
        $this->fireEvent('webmoneymerchant.error');
        NotificationFacade::assertSentTo(new WebMoneyMerchantNotifiable(), WebMoneyMerchantNotification::class);
    }

    /** @test */
    public function it_can_send_notification_when_payment_success()
    {
        $this->fireEvent('webmoneymerchant.success');
        NotificationFacade::assertSentTo(new WebMoneyMerchantNotifiable(), WebMoneyMerchantNotification::class);
    }

    /** @test */
    public function it_can_send_notification_when_job_failed_to_different_notifiable()
    {
        $this->app['config']->set('webmoney-merchant.notifiable', AnotherNotifiable::class);
        $this->fireEvent('webmoneymerchant.success');
        NotificationFacade::assertSentTo(new AnotherNotifiable(), WebMoneyMerchantNotification::class);
    }

    /** @test */
    public function it_can_send_notification_when_job_failed_to_different_notification()
    {
        $this->app['config']->set('webmoney-merchant.notification', AnotherNotification::class);
        $this->fireEvent('webmoneymerchant.success');
        NotificationFacade::assertSentTo(new WebMoneyMerchantNotifiable(), AnotherNotification::class);
    }

    /** @test */
    public function it_filters_out_notifications_when_the_notificationFilter_returns_true()
    {
        $this->app['config']->set('webmoney-merchant.notificationFilter', [$this, 'returnsTrueWhenTypeIsEmpty']);
        $this->fireEvent('webmoneymerchant.success');
        NotificationFacade::assertSentTo(new WebMoneyMerchantNotifiable(), WebMoneyMerchantNotification::class);
    }

    /** @test */
    public function it_filters_out_notifications_when_the_notificationFilter_returns_false()
    {
        $this->app['config']->set('webmoney-merchant.notificationFilter', [$this, 'returnsFalseWhenTypeIsEmpty']);
        $this->fireEvent('webmoneymerchant.success');
        NotificationFacade::assertNotSentTo(new WebMoneyMerchantNotifiable(), WebMoneyMerchantNotification::class);
    }

    /** @test */
    public function check_if_allow_remote_ip()
    {
        $this->app['config']->set('webmoney-merchant.allowed_ips', ['*']);

        $this->assertTrue(
            $this->webmoneymerchant->allowIP('255.255.255.0')
        );

        $this->app['config']->set('webmoney-merchant.allowed_ips', ['123.123.123.123']);

        $this->assertTrue(
            $this->webmoneymerchant->allowIP('127.0.0.1')
        );

        $this->app['config']->set('webmoney-merchant.allowed_ips', ['123.123.123.123']);
        $this->assertFalse(
            $this->webmoneymerchant->allowIP('0.0.0.0')
        );
    }

    /** @test */
    public function compare_form_signature()
    {
        $this->assertEquals(
            '10ea4f78a62b8da4256c1e9f34fa6c06ba4df313571e247ef511311caf8b9f9d',
            $this->webmoneymerchant->getFormSignature('1', '1')
        );
    }

    /** @test */
    public function compare_request_signature()
    {
        $params = [
            'LMI_PAYEE_PURSE' => '1',
            'LMI_PAYMENT_AMOUNT' => '1',
            'LMI_PAYMENT_NO' => '1',
            'LMI_MODE' => '0',
            'LMI_SYS_INVS_NO' => '1',
            'LMI_SYS_TRANS_NO' => '1',
            'LMI_SYS_TRANS_DATE' => '1',
            'LMI_PAYER_WM' => '1',
        ];

        $this->assertEquals(
            '0311c76dc7a47b13d479387b8ce696093c0bd2d232193e14c9c747420a3643c1',
            $this->webmoneymerchant->getSignature(new Request($params))
        );
    }

    /** @test */
    public function generate_order_validation_true()
    {
        $this->assertArrayHasKey('PAYMENT_AMOUNT', $this->webmoneymerchant->generateWebMoneyMerchantOrderWithRequiredFields('999', '12345', 'Item name'));
    }

    /** @test */
    public function generate_order_true_validation_false()
    {
        $this->expectException('ActionM\WebMoneyMerchant\Exceptions\InvalidConfiguration');
        $this->webmoneymerchant->generateWebMoneyMerchantOrderWithRequiredFields('', '', 'Item name');
    }

    /** @test */
    public function generate_payment_form()
    {
        $this->assertNotNull($this->webmoneymerchant->generatePaymentForm('999', '12345', 'Item name'));
        $this->assertEquals('webmoney-merchant::payment_form', $this->webmoneymerchant->generatePaymentForm('999', '12345', 'Item name')->getName());
    }

    /** @test */
    public function validate_signature()
    {
        $request = $this->create_test_request('0311c76dc7a47b13d479387b8ce696093c0bd2d232193e14c9c747420a3643c1');

        $this->assertTrue($this->webmoneymerchant->validate($request));
        $this->assertTrue($this->webmoneymerchant->validateSignature($request));

        $request = $this->create_test_request('invalid_signature');

        $this->assertTrue($this->webmoneymerchant->validate($request));
        $this->assertFalse($this->webmoneymerchant->validateSignature($request));
    }

    /** @test */
    public function test_order_need_callbacks()
    {
        $request = $this->create_test_request();

        $this->expectException('ActionM\WebMoneyMerchant\Exceptions\InvalidConfiguration');
        $this->webmoneymerchant->callFilterSearchOrder($request);

        $this->expectException('ActionM\WebMoneyMerchant\Exceptions\InvalidConfiguration');
        $this->webmoneymerchant->callFilterPaidOrder($request, ['order_id' => '12345']);
    }

    /** @test */
    public function search_order_has_callbacks_fails_and_notify()
    {
        $this->app['config']->set('webmoney-merchant.searchOrderFilter', [Order::class, 'SearchOrderFilterFails']);
        $request = $this->create_test_request();

        $this->assertFalse($this->webmoneymerchant->callFilterSearchOrder($request));
        NotificationFacade::assertSentTo(new WebMoneyMerchantNotifiable(), WebMoneyMerchantNotification::class);
    }

    /** @test */
    public function validate_search_order_required_attributes_not_set()
    {
        $request = new Request([
            'params' => [
                'WEBMONEY_orderStatus' => 'paid',
                'WEBMONEY_orderSum' => '0',

            ],
        ]);

        $this->assertFalse($this->webmoneymerchant->validateSearchOrderRequiredAttributes($request, new Order()));
        NotificationFacade::assertSentTo(new WebMoneyMerchantNotifiable(), WebMoneyMerchantNotification::class);
    }

    /** @test */
    public function validate_search_order_required_attributes_true()
    {
        $request = new Request([
            'LMI_PAYMENT_AMOUNT' => '999',
        ]);

        $order = new Order([
            'WEBMONEY_orderSum' =>  '999',
            'WEBMONEY_orderStatus' => 'paid',
        ]);

        $this->assertTrue($this->webmoneymerchant->validateSearchOrderRequiredAttributes($request, $order));
    }

    /** @test */
    public function validate_search_order_required_attributes_compare_sum_false()
    {
        $request = new Request([
            'LMI_PAYMENT_AMOUNT' => '1',
        ]);

        $order = new Order([
            'WEBMONEY_orderSum' =>  '999',
            'WEBMONEY_orderStatus' => 'paid',
        ]);

        $this->assertFalse($this->webmoneymerchant->validateSearchOrderRequiredAttributes($request, $order));
        NotificationFacade::assertSentTo(new WebMoneyMerchantNotifiable(), WebMoneyMerchantNotification::class);
    }

    /** @test */
    public function paid_order_has_callbacks()
    {
        $this->app['config']->set('webmoney-merchant.searchOrderFilter', [Order::class, 'SearchOrderFilterPaid']);
        $this->app['config']->set('webmoney-merchant.paidOrderFilter', [Order::class, 'PaidOrderFilter']);
        $request = $this->create_test_request();
        $this->assertTrue($this->webmoneymerchant->callFilterPaidOrder($request, ['order_id' => '12345']));
    }

    /** @test */
    public function paid_order_has_callbacks_fails()
    {
        $this->app['config']->set('webmoney-merchant.paidOrderFilter', [Order::class, 'PaidOrderFilterFails']);
        $request = $this->create_test_request();
        $this->assertFalse($this->webmoneymerchant->callFilterPaidOrder($request, ['order_id' => '12345']));
    }

    /** @test */
    public function payOrderFromGate_SearchOrderFilter_fails()
    {
        $this->app['config']->set('webmoney-merchant.searchOrderFilter', [Order::class, 'SearchOrderFilterFails']);
        $request = $this->create_test_request();
        $request->server->set('REMOTE_ADDR', '127.0.0.1');
        $this->expectException('Symfony\Component\HttpKernel\Exception\HttpException');
        $this->webmoneymerchant->payOrderFromGate($request);
    }

    /** @test */
    public function payOrderFromGate_method_check_SearchOrderFilterPrerequest()
    {
        $this->app['config']->set('webmoney-merchant.searchOrderFilter', [Order::class, 'SearchOrderFilterPaidforPayOrderFromGate']);
        $request = $this->create_test_request();

        $request['LMI_PREREQUEST'] = '1';
        $request->server->set('REMOTE_ADDR', '127.0.0.1');

        $this->assertEquals('YES', $this->webmoneymerchant->payOrderFromGate($request));
    }

    /** @test */
    public function payOrderFromGate_method_pay_SearchOrderFilterAlreadyPaid()
    {
        $this->app['config']->set('webmoney-merchant.searchOrderFilter', [Order::class, 'SearchOrderFilterPaidforPayOrderFromGate']);
        $this->app['config']->set('webmoney-merchant.paidOrderFilter', [Order::class, 'PaidOrderFilter']);
        $this->app['config']->set('webmoney-merchant.WM_LMI_PAYEE_PURSE', '1');

        $request = $this->create_test_request();
        $request->server->set('REMOTE_ADDR', '127.0.0.1');

        $this->expectException('Symfony\Component\HttpKernel\Exception\HttpException');

        $this->webmoneymerchant->payOrderFromGate($request);

        NotificationFacade::assertSentTo(new WebMoneyMerchantNotifiable(), WebMoneyMerchantNotification::class);
    }

    /** @test */
    public function payOrderFromGate_method_pay_SearchOrderFilterPaid()
    {
        $this->app['config']->set('webmoney-merchant.searchOrderFilter', [Order::class, 'SearchOrderFilterNotPaid']);
        $this->app['config']->set('webmoney-merchant.paidOrderFilter', [Order::class, 'PaidOrderFilter']);
        $this->app['config']->set('webmoney-merchant.WM_LMI_PAYEE_PURSE', '1');

        $request = $this->create_test_request();
        $request->server->set('REMOTE_ADDR', '127.0.0.1');

        $this->assertEquals('YES', $this->webmoneymerchant->payOrderFromGate($request));

        NotificationFacade::assertSentTo(new WebMoneyMerchantNotifiable(), WebMoneyMerchantNotification::class);
    }
}
