<?php

namespace App\Console\Commands;

use App\Models\Booking;
use App\Telegram\HotelBookingBot\Commands\CheckInReminderCommand;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SendCheckInReminders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'reminders:check-in {--type=all}';

    /**
     * The description of the console command.
     *
     * @var string
     */
    protected $description = 'Send check-in reminders via Telegram (24h and 2h before check-in)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $type = $this->option('type');

        try {
            if ($type === 'all' || $type === '24h') {
                $this->send24HourReminders();
            }

            if ($type === 'all' || $type === '2h') {
                $this->send2HourReminders();
            }

            $this->info('Check-in reminders sent successfully.');
            return Command::SUCCESS;
        } catch (\Exception $e) {
            Log::error('Error sending check-in reminders', ['error' => $e->getMessage()]);
            $this->error('Error sending check-in reminders: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }

    /**
     * Send 24-hour reminders
     */
    private function send24HourReminders(): void
    {
        // Get bookings checking in tomorrow
        $tomorrow = Carbon::tomorrow();
        $bookings = Booking::where('status', 'confirmed')
            ->where('check_in_date', $tomorrow)
            ->with(['user', 'room'])
            ->get();

        $this->info("Sending 24-hour reminders for {$bookings->count()} bookings...");

        foreach ($bookings as $booking) {
            try {
                // Check if user has Telegram linked
                if (!$booking->user->telegram_user_id) {
                    Log::warning('User has no Telegram ID', ['user_id' => $booking->user->id]);
                    continue;
                }

                $command = new CheckInReminderCommand(
                    $booking->user->telegram_user_id,
                    $booking->user->id
                );

                $result = $command->send24HourReminder($booking);

                if ($result['success']) {
                    $this->line("✓ 24h reminder sent for booking #{$booking->id}");
                } else {
                    $this->warn("✗ Failed to send 24h reminder for booking #{$booking->id}");
                }
            } catch (\Exception $e) {
                Log::error('Error sending 24h reminder', [
                    'booking_id' => $booking->id,
                    'error' => $e->getMessage(),
                ]);
                $this->warn("✗ Error sending 24h reminder for booking #{$booking->id}");
            }
        }
    }

    /**
     * Send 2-hour reminders
     */
    private function send2HourReminders(): void
    {
        // Get bookings checking in within 2 hours
        $now = now();
        $twoHoursLater = $now->copy()->addHours(2);

        $bookings = Booking::where('status', 'confirmed')
            ->where('check_in_date', $now->toDateString())
            ->with(['user', 'room'])
            ->get()
            ->filter(function ($booking) use ($now, $twoHoursLater) {
                // Check if check-in time is within 2 hours
                $checkInTime = $booking->check_in_date->copy()->setTime(14, 0); // 14:00 check-in
                return $checkInTime->between($now, $twoHoursLater);
            });

        $this->info("Sending 2-hour reminders for {$bookings->count()} bookings...");

        foreach ($bookings as $booking) {
            try {
                // Check if user has Telegram linked
                if (!$booking->user->telegram_user_id) {
                    Log::warning('User has no Telegram ID', ['user_id' => $booking->user->id]);
                    continue;
                }

                $command = new CheckInReminderCommand(
                    $booking->user->telegram_user_id,
                    $booking->user->id
                );

                $result = $command->send2HourReminder($booking);

                if ($result['success']) {
                    $this->line("✓ 2h reminder sent for booking #{$booking->id}");
                } else {
                    $this->warn("✗ Failed to send 2h reminder for booking #{$booking->id}");
                }
            } catch (\Exception $e) {
                Log::error('Error sending 2h reminder', [
                    'booking_id' => $booking->id,
                    'error' => $e->getMessage(),
                ]);
                $this->warn("✗ Error sending 2h reminder for booking #{$booking->id}");
            }
        }
    }
}
