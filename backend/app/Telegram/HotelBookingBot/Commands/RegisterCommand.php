<?php

namespace App\Telegram\HotelBookingBot\Commands;

use App\Models\User;
use App\Models\TelegramVerification;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class RegisterCommand
{
    /**
     * Telegram user ID
     */
    private int $telegramUserId;

    /**
     * Chat ID
     */
    private int $chatId;

    /**
     * Constructor
     */
    public function __construct(int $telegramUserId, int $chatId)
    {
        $this->telegramUserId = $telegramUserId;
        $this->chatId = $chatId;
    }

    /**
     * Start registration process
     */
    public function startRegistration(): array
    {
        return [
            'success' => true,
            'message' => "<b>🔐 Account Registration</b>\n\n"
                . "Do you already have an account with us?\n\n"
                . "If yes, enter your registered email address to link your Telegram account.\n"
                . "If no, we'll help you create a new account.\n\n"
                . "Please reply with your email address:",
            'action' => 'awaiting_email',
        ];
    }

    /**
     * Handle email submission
     */
    public function handleEmailSubmission(string $email): array
    {
        // Validate email format
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return [
                'success' => false,
                'message' => "❌ Invalid email format. Please enter a valid email address.",
            ];
        }

        // Check if user exists
        $user = User::where('email', $email)->first();

        if ($user) {
            return $this->sendVerificationCodeToExistingUser($user);
        } else {
            return $this->offerNewAccountCreation($email);
        }
    }

    /**
     * Send verification code to existing user
     */
    private function sendVerificationCodeToExistingUser(User $user): array
    {
        try {
            // Generate 6-digit code
            $code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

            // Store verification record
            TelegramVerification::updateOrCreate(
                ['telegram_user_id' => $this->telegramUserId],
                [
                    'user_id' => $user->id,
                    'email' => $user->email,
                    'code' => $code,
                    'code_expires_at' => now()->addMinutes(10),
                    'attempts' => 0,
                    'status' => 'pending',
                ]
            );

            // Send email with code
            Mail::send('emails.telegram-verification', [
                'user' => $user,
                'code' => $code,
                'expiresIn' => '10 minutes',
            ], function ($message) use ($user) {
                $message->to($user->email)
                    ->subject('Telegram Account Verification - SATAAB Hotel');
            });

            Log::info('Verification code sent', [
                'telegram_user_id' => $this->telegramUserId,
                'user_id' => $user->id,
                'email' => $user->email,
            ]);

            return [
                'success' => true,
                'message' => "✅ <b>Verification Code Sent</b>\n\n"
                    . "We've sent a 6-digit verification code to:\n"
                    . "<b>{$user->email}</b>\n\n"
                    . "Please enter the code below:\n"
                    . "(Code expires in 10 minutes)",
                'action' => 'awaiting_verification_code',
                'user_id' => $user->id,
            ];
        } catch (\Exception $e) {
            Log::error('Error sending verification code', [
                'error' => $e->getMessage(),
                'user_id' => $user->id,
            ]);

            return [
                'success' => false,
                'message' => "❌ Error sending verification code. Please try again later.",
            ];
        }
    }

    /**
     * Offer new account creation
     */
    private function offerNewAccountCreation(string $email): array
    {
        // Store email for new account creation
        TelegramVerification::updateOrCreate(
            ['telegram_user_id' => $this->telegramUserId],
            [
                'email' => $email,
                'status' => 'new_account',
                'created_at' => now(),
            ]
        );

        return [
            'success' => true,
            'message' => "📝 <b>Create New Account</b>\n\n"
                . "We don't have an account with this email.\n\n"
                . "Let's create a new account for you!\n\n"
                . "Please provide the following information:\n\n"
                . "1️⃣ <b>Full Name:</b>",
            'action' => 'awaiting_name',
            'email' => $email,
        ];
    }

    /**
     * Verify code
     */
    public function verifyCode(string $code): array
    {
        try {
            $verification = TelegramVerification::where('telegram_user_id', $this->telegramUserId)
                ->where('status', 'pending')
                ->first();

            if (!$verification) {
                return [
                    'success' => false,
                    'message' => "❌ No verification request found. Please start over.",
                ];
            }

            // Check if code expired
            if ($verification->code_expires_at < now()) {
                $verification->update(['status' => 'expired']);
                return [
                    'success' => false,
                    'message' => "⏰ <b>Code Expired</b>\n\n"
                        . "Your verification code has expired.\n"
                        . "Please start the registration process again.",
                ];
            }

            // Check attempts
            if ($verification->attempts >= 3) {
                $verification->update(['status' => 'blocked']);
                return [
                    'success' => false,
                    'message' => "🚫 <b>Too Many Attempts</b>\n\n"
                        . "You've exceeded the maximum number of attempts.\n"
                        . "Please try again later or contact support.",
                ];
            }

            // Verify code
            if ($verification->code !== $code) {
                $verification->increment('attempts');
                $remaining = 3 - $verification->attempts;
                return [
                    'success' => false,
                    'message' => "❌ <b>Invalid Code</b>\n\n"
                        . "The code you entered is incorrect.\n"
                        . "Remaining attempts: {$remaining}",
                ];
            }

            // Code is valid - link account
            return $this->linkTelegramAccount($verification);
        } catch (\Exception $e) {
            Log::error('Error verifying code', [
                'error' => $e->getMessage(),
                'telegram_user_id' => $this->telegramUserId,
            ]);

            return [
                'success' => false,
                'message' => "❌ Error verifying code. Please try again later.",
            ];
        }
    }

    /**
     * Link Telegram account to user
     */
    private function linkTelegramAccount(TelegramVerification $verification): array
    {
        try {
            $user = User::find($verification->user_id);

            if (!$user) {
                return [
                    'success' => false,
                    'message' => "❌ User account not found.",
                ];
            }

            // Update user with Telegram info
            $user->update([
                'telegram_user_id' => $this->telegramUserId,
                'telegram_chat_id' => $this->chatId,
                'telegram_linked_at' => now(),
            ]);

            // Mark verification as completed
            $verification->update(['status' => 'verified']);

            Log::info('Telegram account linked', [
                'user_id' => $user->id,
                'telegram_user_id' => $this->telegramUserId,
            ]);

            return [
                'success' => true,
                'message' => "✅ <b>Account Linked Successfully!</b>\n\n"
                    . "Welcome back, <b>{$user->name}</b>! 🎉\n\n"
                    . "Your Telegram account is now linked to your hotel account.\n"
                    . "You can now use all bot features!\n\n"
                    . "📧 Email: {$user->email}\n"
                    . "📱 Phone: {$user->phone}\n\n"
                    . "Type /start to see the main menu.",
                'action' => 'completed',
                'user' => $user,
            ];
        } catch (\Exception $e) {
            Log::error('Error linking Telegram account', [
                'error' => $e->getMessage(),
                'telegram_user_id' => $this->telegramUserId,
            ]);

            return [
                'success' => false,
                'message' => "❌ Error linking account. Please try again later.",
            ];
        }
    }

    /**
     * Create new account
     */
    public function createNewAccount(array $data): array
    {
        try {
            // Validate data
            $validation = $this->validateNewAccountData($data);
            if (!$validation['valid']) {
                return [
                    'success' => false,
                    'message' => $validation['message'],
                ];
            }

            // Check if email already exists
            if (User::where('email', $data['email'])->exists()) {
                return [
                    'success' => false,
                    'message' => "❌ Email already registered. Please use the login option.",
                ];
            }

            // Generate temporary password
            $tempPassword = Str::random(12);

            // Create user
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'phone' => $data['phone'] ?? null,
                'password' => bcrypt($tempPassword),
                'telegram_user_id' => $this->telegramUserId,
                'telegram_chat_id' => $this->chatId,
                'telegram_linked_at' => now(),
                'email_verified_at' => now(),
            ]);

            // Send welcome email with temporary password
            Mail::send('emails.telegram-account-created', [
                'user' => $user,
                'tempPassword' => $tempPassword,
                'loginUrl' => config('app.frontend_url') . '/login',
            ], function ($message) use ($user) {
                $message->to($user->email)
                    ->subject('Welcome to SATAAB Hotel - Account Created');
            });

            // Mark verification as completed
            TelegramVerification::where('telegram_user_id', $this->telegramUserId)
                ->update(['status' => 'verified', 'user_id' => $user->id]);

            Log::info('New user account created via Telegram', [
                'user_id' => $user->id,
                'telegram_user_id' => $this->telegramUserId,
            ]);

            return [
                'success' => true,
                'message' => "✅ <b>Account Created Successfully!</b>\n\n"
                    . "Welcome to SATAAB Hotel, <b>{$user->name}</b>! 🎉\n\n"
                    . "Your account has been created and linked to Telegram.\n\n"
                    . "📧 Email: {$user->email}\n"
                    . "📱 Phone: {$user->phone}\n\n"
                    . "A temporary password has been sent to your email.\n"
                    . "You can change it anytime in your profile settings.\n\n"
                    . "Type /start to see the main menu.",
                'action' => 'completed',
                'user' => $user,
            ];
        } catch (\Exception $e) {
            Log::error('Error creating new account', [
                'error' => $e->getMessage(),
                'telegram_user_id' => $this->telegramUserId,
            ]);

            return [
                'success' => false,
                'message' => "❌ Error creating account. Please try again later.",
            ];
        }
    }

    /**
     * Validate new account data
     */
    private function validateNewAccountData(array $data): array
    {
        if (empty($data['name']) || strlen($data['name']) < 2) {
            return [
                'valid' => false,
                'message' => "❌ Name must be at least 2 characters long.",
            ];
        }

        if (empty($data['email']) || !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            return [
                'valid' => false,
                'message' => "❌ Invalid email address.",
            ];
        }

        if (!empty($data['phone']) && strlen($data['phone']) < 7) {
            return [
                'valid' => false,
                'message' => "❌ Phone number must be at least 7 digits.",
            ];
        }

        return ['valid' => true];
    }
}
