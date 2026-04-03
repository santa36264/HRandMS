<?php

namespace App\Providers;

use App\Telegram\HotelBookingBot\BotFactory;
use Illuminate\Support\ServiceProvider;

class TelegramServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->registerBotFactory();
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }

    /**
     * Register bot factory
     */
    private function registerBotFactory(): void
    {
        $this->app->singleton('telegram.bot.factory', function () {
            return new BotFactory();
        });
    }
}
