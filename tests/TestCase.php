<?php

namespace ActionM\WebMoneyMerchant\Test;

use ActionM\WebMoneyMerchant\WebMoneyMerchant;
use Orchestra\Testbench\TestCase as Orchestra;
use ActionM\WebMoneyMerchant\WebMoneyMerchantServiceProvider;
use Illuminate\Support\Facades\Notification as NotificationFacade;

class TestCase extends Orchestra
{
    protected $webmoneymerchant;

    public function setUp()
    {
        parent::setUp();
        $this->webmoneymerchant = $this->app['webmoneymerchant'];

        NotificationFacade::fake();

        $this->app['config']->set('webmoney-merchant.WM_LMI_PAYEE_PURSE', 'Z1234567890');
        $this->app['config']->set('webmoney-merchant.WM_LMI_SECRET_X20', 'secret_key_X20');
        $this->app['config']->set('webmoney-merchant.WM_LMI_SECRET_KEY', 'secret_key');
    }

    protected function getPackageProviders($app)
    {
        return [
            WebMoneyMerchantServiceProvider::class,
        ];
    }

    protected function withConfig(array $config)
    {
        $this->app['config']->set($config);
        $this->app->forgetInstance(WebMoneyMerchant::class);
        $this->webmoneymerchant = $this->app->make(WebMoneyMerchant::class);
    }
}
