<?php

namespace App\Telegram\HotelBookingBot;

use App\Services\RoomService;
use App\Services\BookingService;
use Illuminate\Container\Container;

class BotFactory
{
    /**
     * Create a bot instance
     */
    public static function create(string $botType = 'default'): BaseBot
    {
        $container = Container::getInstance();

        return match ($botType) {
            'default' => new HotelBookingBot(
                $container->make(RoomService::class),
                $container->make(BookingService::class)
            ),
            default => throw new \InvalidArgumentException("Unknown bot type: {$botType}"),
        };
    }
}
