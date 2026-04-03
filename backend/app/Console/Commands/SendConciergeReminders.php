<?php

namespace App\Console\Commands;

use App\Models\ConciergeBooking;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SendConciergeReminders extends Command
{
    protected $signature = 'concierge:send-reminders';
    protected $description = 'Send reminders for upcoming concierge services';

    public function handle()
    {
        try {
            // Send 24-hour reminders
            $this->send24HourReminders();

            // Send 2-hour reminders
            $this->send2HourReminders();

            $this->info('Concierge reminders sent successfully.');
        } catch (\Exception $e) {
            Log::error('Error sending concierge reminders', ['error' => $e->getMessage()]);
            $this->error('Error sending reminders: ' . $e->getMessage());
        }
    }

    /**
     * Send 24-hour reminders
     */
    private function send24HourReminders(): void
    {
        $bookings = ConciergeBooking::where('status', 'confirmed')
            ->whereBetween('scheduled_time', [
                now()->addHours(23)->startOfHour(),
                now()->addHours(24)->endOfHour(),
            ])
            ->get();

        foreach ($bookings as $booking) {
            $this->sendReminder($booking, '24-hour');
        }
    }

    /**
     * Send 2-hour reminders
     */
    private function send2HourReminders(): void
    {
        $bookings = ConciergeBooking::where('status', 'confirmed')
            ->whereBetween('scheduled_time', [
                now()->addHours(1)->startOfHour(),
                now()->addHours(2)->endOfHour(),
            ])
            ->get();

        foreach ($bookings as $booking) {
            $this->sendReminder($booking, '2-hour');
        }
    }

    /**
     * Send reminder
     */
    private function sendReminder(ConciergeBooking $booking, string $type): void
    {
        try {
            $service = $booking->service;
            $user = $booking->user;

            $message = match ($type) {
                '24-hour' => $this->format24HourReminder($booking, $service),
                '2-hour' => $this->format2HourReminder($booking, $service),
                default => '',
            };

            Log::info('Concierge Reminder - Telegram Notification', [
                'booking_id' => $booking->id,
                'user_id' => $user->id,
                'telegram_id' => $user->telegram_id,
                'service_type' => $service->type,
                'service_name' => $service->name,
                'scheduled_time' => $booking->scheduled_time,
                'reminder_type' => $type,
                'message' => $message,
            ]);
        } catch (\Exception $e) {
            Log::error('Error sending concierge reminder', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Format 24-hour reminder
     */
    private function format24HourReminder(ConciergeBooking $booking, $service): string
    {
        $message = "🎩 <b>Concierge Service Reminder</b>\n\n";
        $message .= "Your {$service->name} is scheduled for tomorrow!\n\n";
        $message .= "📅 <b>Date & Time:</b> {$booking->scheduled_time->format('M d, Y H:i')}\n";
        $message .= "🎯 <b>Service:</b> {$service->name}\n";
        $message .= "💰 <b>Amount:</b> {$booking->total_amount} ETB\n";
        $message .= "📌 <b>Confirmation:</b> {$booking->confirmation_code}\n";

        if ($service->provider_name) {
            $message .= "👤 <b>Provider:</b> {$service->provider_name}\n";
        }

        if ($service->provider_phone) {
            $message .= "📞 <b>Contact:</b> {$service->provider_phone}\n";
        }

        $message .= "\nPlease confirm your attendance or contact us if you need to reschedule.";

        return $message;
    }

    /**
     * Format 2-hour reminder
     */
    private function format2HourReminder(ConciergeBooking $booking, $service): string
    {
        $message = "⏰ <b>Concierge Service - 2 Hour Reminder</b>\n\n";
        $message .= "Your {$service->name} is starting soon!\n\n";
        $message .= "📅 <b>Time:</b> {$booking->scheduled_time->format('H:i')}\n";
        $message .= "🎯 <b>Service:</b> {$service->name}\n";
        $message .= "📌 <b>Confirmation:</b> {$booking->confirmation_code}\n";

        if ($service->provider_phone) {
            $message .= "📞 <b>Contact:</b> {$service->provider_phone}\n";
        }

        $message .= "\nPlease be ready. Your service provider will arrive shortly.";

        return $message;
    }
}
