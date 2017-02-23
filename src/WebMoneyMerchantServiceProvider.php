<?php

namespace ActionM\WebMoneyMerchant;

use Illuminate\Support\Facades\App;
use Illuminate\Support\ServiceProvider;
use ActionM\WebMoneyMerchant\Exceptions\InvalidConfiguration;

class WebMoneyMerchantServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     */
    public function boot()
    {
        $this->publishes([
            __DIR__.'/../config/webmoney-merchant.php' => config_path('webmoney-merchant.php'),
        ], 'config');

        $this->publishes([
            __DIR__.'/../resources/views' => base_path('resources/views/vendor/webmoney-merchant'),
        ], 'views');

        $this->loadViewsFrom(__DIR__.'/../resources/views', 'webmoney-merchant');

        $this->testingEnv();
    }

    /**
     * Register the application services.
     */
    public function register()
    {
        $this->mergeConfigFrom(__DIR__.'/../config/webmoney-merchant.php', 'webmoney-merchant');

        $this->app['events']->subscribe(WebMoneyMerchantNotifier::class);

        $this->app->singleton('webmoneymerchant', function () {
            return $this->app->make('ActionM\WebMoneyMerchant\WebMoneyMerchant');
        });

        $this->app->alias('webmoneymerchant', 'WebMoneyMerchant');

        $this->app->singleton(WebMoneyMerchantNotifier::class);
    }

    /**
     * Not check config if testing env.
     * @throws InvalidConfiguration
     */
    public function testingEnv()
    {
        if (! App::environment('testing')) {
            $callable = config('webmoney-merchant.searchOrderFilter');

            if (! is_callable($callable)) {
                throw InvalidConfiguration::searchOrderFilterInvalid();
            }

            $callable = config('webmoney-merchant.paidOrderFilter');

            if (! is_callable($callable)) {
                throw InvalidConfiguration::orderPaidFilterInvalid();
            }
        }
    }
}
