<?php

namespace App\Telegram\HotelBookingBot\Commands;

use Illuminate\Support\Facades\Log;

class HelpCallbackHandler
{
    /**
     * Chat ID
     */
    private int $chatId;

    /**
     * Callback data
     */
    private string $callbackData;

    /**
     * Callback ID
     */
    private string $callbackId;

    /**
     * Constructor
     */
    public function __construct(int $chatId, string $callbackData, string $callbackId)
    {
        $this->chatId = $chatId;
        $this->callbackData = $callbackData;
        $this->callbackId = $callbackId;
    }

    /**
     * Handle help callback
     */
    public function handle(): array
    {
        try {
            $action = str_replace('help_', '', $this->callbackData);

            $response = match ($action) {
                'commands' => $this->handleCommands(),
                'faq' => $this->handleFAQ(),
                'contact' => $this->handleContact(),
                'emergency' => $this->handleEmergency(),
                default => ['message' => '❌ Unknown action'],
            };

            return [
                'success' => true,
                'callback_id' => $this->callbackId,
                'response' => $response,
            ];
        } catch (\Exception $e) {
            Log::error('Error handling help callback', [
                'error' => $e->getMessage(),
                'callback_data' => $this->callbackData,
            ]);

            return [
                'success' => false,
                'callback_id' => $this->callbackId,
                'message' => '❌ Error processing request',
            ];
        }
    }

    /**
     * Handle commands section
     */
    private function handleCommands(): array
    {
        $helpContent = HelpCommand::getHelpContent();

        return [
            'message' => $helpContent['commands'],
            'keyboard' => [
                [
                    ['text' => '❓ FAQ', 'callback_data' => 'help_faq'],
                    ['text' => '📞 Contact', 'callback_data' => 'help_contact'],
                ],
                [
                    ['text' => '⬅️ Back', 'callback_data' => 'menu_help'],
                ]
            ],
        ];
    }

    /**
     * Handle FAQ section
     */
    private function handleFAQ(): array
    {
        $helpContent = HelpCommand::getHelpContent();

        // Split FAQ into multiple messages if too long
        $faqText = $helpContent['faq'];
        $maxLength = 4096; // Telegram message limit

        if (strlen($faqText) > $maxLength) {
            // Split into parts
            $parts = str_split($faqText, $maxLength);
            $message = $parts[0] . "\n\n<i>... (continued in next message)</i>";
        } else {
            $message = $faqText;
        }

        return [
            'message' => $message,
            'keyboard' => [
                [
                    ['text' => '📋 Commands', 'callback_data' => 'help_commands'],
                    ['text' => '📞 Contact', 'callback_data' => 'help_contact'],
                ],
                [
                    ['text' => '⬅️ Back', 'callback_data' => 'menu_help'],
                ]
            ],
        ];
    }

    /**
     * Handle contact section
     */
    private function handleContact(): array
    {
        $helpContent = HelpCommand::getHelpContent();

        return [
            'message' => $helpContent['contact'],
            'keyboard' => [
                [
                    ['text' => '💬 WhatsApp', 'url' => 'https://wa.me/251911234567'],
                    ['text' => '📧 Email', 'url' => 'mailto:info@sataabhotel.com'],
                ],
                [
                    ['text' => '🌐 Website', 'url' => 'https://www.sataabhotel.com'],
                    ['text' => '📱 Call', 'url' => 'tel:+251911234567'],
                ],
                [
                    ['text' => '⬅️ Back', 'callback_data' => 'menu_help'],
                ]
            ],
        ];
    }

    /**
     * Handle emergency section
     */
    private function handleEmergency(): array
    {
        $helpContent = HelpCommand::getHelpContent();

        return [
            'message' => $helpContent['emergency'],
            'keyboard' => [
                [
                    ['text' => '🚨 Call Emergency', 'url' => 'tel:911'],
                    ['text' => '📱 Hotel Emergency', 'url' => 'tel:+251911234567'],
                ],
                [
                    ['text' => '⬅️ Back', 'callback_data' => 'menu_help'],
                ]
            ],
        ];
    }
}
