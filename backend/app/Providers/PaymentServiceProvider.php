<?php

namespace App\Providers;

use App\Payments\Api\ChapaApi;
use App\Payments\Gateways\ChapaGateway;
use App\Payments\PaymentManager;
use Illuminate\Support\ServiceProvider;

class PaymentServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(ChapaApi::class);

        $this->app->singleton(PaymentManager::class, function ($app) {
            $manager = new PaymentManager();
            $manager->register(new ChapaGateway($app->make(ChapaApi::class)));
            return $manager;
        });
    }

    public function boot(): void {}
}
