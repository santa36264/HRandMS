<?php

namespace Tests\Unit\Telegram;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

class TelegramApiMockTest extends TestCase
{
    use RefreshDatabase;

    protected string $botToken = '8596964164:AAFN2muTzSjo10jHk1_pdiKRvfs3GtMb92U';
    protected string $telegramApiUrl = 'https://api.telegram.org/bot';

    /**
     * Test mock send message API call
     */
    public function test_mock_send_message_api_call(): void
    {
        Http::fake([
            $this->telegramApiUrl . $this->botToken . '/sendMessage' => Http::response([
                'ok' => true,
                'result' => [
                    'message_id' => 123,
                    'date' => time(),
                    'chat' => ['id' => 987654321],
                    'text' => 'Test message',
                ],
            ]),
        ]);

        $response = Http::post($this->telegramApiUrl . $this->botToken . '/sendMessage', [
            'chat_id' => 987654321,
            'text' => 'Test message',
        ]);

        $this->assertTrue($response->json('ok'));
        $this->assertEquals('Test message', $response->json('result.text'));
    }

    /**
     * Test mock send photo API call
     */
    public function test_mock_send_photo_api_call(): void
    {
        Http::fake([
            $this->telegramApiUrl . $this->botToken . '/sendPhoto' => Http::response([
                'ok' => true,
                'result' => [
                    'message_id' => 124,
                    'photo' => [
                        ['file_id' => 'photo_123'],
                    ],
                ],
            ]),
        ]);

        $response = Http::post($this->telegramApiUrl . $this->botToken . '/sendPhoto', [
            'chat_id' => 987654321,
            'photo' => 'https://example.com/photo.jpg',
        ]);

        $this->assertTrue($response->json('ok'));
        $this->assertNotEmpty($response->json('result.photo'));
    }

    /**
     * Test mock send inline keyboard API call
     */
    public function test_mock_send_inline_keyboard_api_call(): void
    {
        Http::fake([
            $this->telegramApiUrl . $this->botToken . '/sendMessage' => Http::response([
                'ok' => true,
                'result' => [
                    'message_id' => 125,
                    'reply_markup' => [
                        'inline_keyboard' => [
                            [
                                ['text' => 'Button 1', 'callback_data' => 'btn_1'],
                                ['text' => 'Button 2', 'callback_data' => 'btn_2'],
                            ],
                        ],
                    ],
                ],
            ]),
        ]);

        $response = Http::post($this->telegramApiUrl . $this->botToken . '/sendMessage', [
            'chat_id' => 987654321,
            'text' => 'Choose an option',
            'reply_markup' => json_encode([
                'inline_keyboard' => [
                    [
                        ['text' => 'Button 1', 'callback_data' => 'btn_1'],
                        ['text' => 'Button 2', 'callback_data' => 'btn_2'],
                    ],
                ],
            ]),
        ]);

        $this->assertTrue($response->json('ok'));
        $this->assertNotEmpty($response->json('result.reply_markup.inline_keyboard'));
    }

    /**
     * Test mock answer callback query API call
     */
    public function test_mock_answer_callback_query_api_call(): void
    {
        Http::fake([
            $this->telegramApiUrl . $this->botToken . '/answerCallbackQuery' => Http::response([
                'ok' => true,
            ]),
        ]);

        $response = Http::post($this->telegramApiUrl . $this->botToken . '/answerCallbackQuery', [
            'callback_query_id' => 'callback_123',
            'text' => 'Processing...',
        ]);

        $this->assertTrue($response->json('ok'));
    }

    /**
     * Test mock edit message text API call
     */
    public function test_mock_edit_message_text_api_call(): void
    {
        Http::fake([
            $this->telegramApiUrl . $this->botToken . '/editMessageText' => Http::response([
                'ok' => true,
                'result' => [
                    'message_id' => 126,
                    'text' => 'Updated message',
                ],
            ]),
        ]);

        $response = Http::post($this->telegramApiUrl . $this->botToken . '/editMessageText', [
            'chat_id' => 987654321,
            'message_id' => 126,
            'text' => 'Updated message',
        ]);

        $this->assertTrue($response->json('ok'));
        $this->assertEquals('Updated message', $response->json('result.text'));
    }

    /**
     * Test mock delete message API call
     */
    public function test_mock_delete_message_api_call(): void
    {
        Http::fake([
            $this->telegramApiUrl . $this->botToken . '/deleteMessage' => Http::response([
                'ok' => true,
            ]),
        ]);

        $response = Http::post($this->telegramApiUrl . $this->botToken . '/deleteMessage', [
            'chat_id' => 987654321,
            'message_id' => 126,
        ]);

        $this->assertTrue($response->json('ok'));
    }

    /**
     * Test mock get me API call
     */
    public function test_mock_get_me_api_call(): void
    {
        Http::fake([
            $this->telegramApiUrl . $this->botToken . '/getMe' => Http::response([
                'ok' => true,
                'result' => [
                    'id' => 123456789,
                    'is_bot' => true,
                    'first_name' => 'SATAAB Hotel Bot',
                    'username' => 'sataab_hotel_reservation_bot',
                ],
            ]),
        ]);

        $response = Http::get($this->telegramApiUrl . $this->botToken . '/getMe');

        $this->assertTrue($response->json('ok'));
        $this->assertEquals('sataab_hotel_reservation_bot', $response->json('result.username'));
    }

    /**
     * Test mock API error response
     */
    public function test_mock_api_error_response(): void
    {
        Http::fake([
            $this->telegramApiUrl . $this->botToken . '/sendMessage' => Http::response([
                'ok' => false,
                'error_code' => 400,
                'description' => 'Bad Request: chat_id is empty',
            ], 400),
        ]);

        $response = Http::post($this->telegramApiUrl . $this->botToken . '/sendMessage', [
            'chat_id' => '',
            'text' => 'Test',
        ]);

        $this->assertFalse($response->json('ok'));
        $this->assertEquals(400, $response->json('error_code'));
    }

    /**
     * Test mock send media group API call
     */
    public function test_mock_send_media_group_api_call(): void
    {
        Http::fake([
            $this->telegramApiUrl . $this->botToken . '/sendMediaGroup' => Http::response([
                'ok' => true,
                'result' => [
                    [
                        'message_id' => 127,
                        'photo' => [['file_id' => 'photo_1']],
                    ],
                    [
                        'message_id' => 128,
                        'photo' => [['file_id' => 'photo_2']],
                    ],
                ],
            ]),
        ]);

        $media = [
            ['type' => 'photo', 'media' => 'https://example.com/photo1.jpg'],
            ['type' => 'photo', 'media' => 'https://example.com/photo2.jpg'],
        ];

        $response = Http::post($this->telegramApiUrl . $this->botToken . '/sendMediaGroup', [
            'chat_id' => 987654321,
            'media' => json_encode($media),
        ]);

        $this->assertTrue($response->json('ok'));
        $this->assertCount(2, $response->json('result'));
    }
}
