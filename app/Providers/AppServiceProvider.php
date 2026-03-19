<?php

namespace App\Providers;

use App\Contracts\PaymentGatewayContract;
use App\Services\BogPaymentService;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        /**
         * Bind PaymentGatewayContract to BogPaymentService implementation
         * Allows dependency injection throughout the application
         * 
         * If switching payment providers, simply swap the implementation:
         * $this->app->bind(PaymentGatewayContract::class, StripePaymentService::class);
         */
        $this->app->bind(
            PaymentGatewayContract::class,
            BogPaymentService::class
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}

