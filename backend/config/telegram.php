<?php

return [
    /**
     * Telegram Bot Token
     */
    'bot_token' => env('TELEGRAM_BOT_TOKEN', '8596964164:AAFN2muTzSjo10jHk1_pdiKRvfs3GtMb92U'),

    /**
     * Multiple bots configuration
     */
    'bots' => [
        'default' => [
            'token' => env('TELEGRAM_BOT_TOKEN', '8596964164:AAFN2muTzSjo10jHk1_pdiKRvfs3GtMb92U'),
        ],
        'admin' => [
            'token' => env('TELEGRAM_ADMIN_BOT_TOKEN', '8596964164:AAFN2muTzSjoi0jHk1_pdiKRvfs3GtMb92U'),
        ],
    ],

    /**
     * Webhook URLs
     */
    'webhook_url' => env('TELEGRAM_WEBHOOK_URL', 'https://your-domain.com/api/telegram-webhook'),
    'admin_webhook_url' => env('TELEGRAM_ADMIN_WEBHOOK_URL', 'https://your-domain.com/api/telegram-webhook/admin'),

    /**
     * Staff Group ID for notifications
     * Get this by adding the bot to a group and sending a message
     * Then check the logs for the group ID
     */
    'staff_group_id' => env('TELEGRAM_STAFF_GROUP_ID', '-1001234567890'),

    /**
     * Admin User IDs (comma-separated)
     * Get your Telegram user ID by sending /start to the bot and checking logs
     */
    'admin_ids' => array_filter(array_map('intval', explode(',', env('TELEGRAM_ADMIN_IDS', '')))),

    /**
     * Hotel name
     */
    'hotel_name' => env('HOTEL_NAME', 'SATAAB Hotel'),

    /**
     * Enable/disable notifications
     */
    'notifications_enabled' => env('TELEGRAM_NOTIFICATIONS_ENABLED', true),
];
