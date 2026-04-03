<?php

namespace App\Console\Commands;

use App\Models\Booking;
use App\Telegram\HotelBookingBot\Commands\CheckOutReminderCommand;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SendCheckOutReminders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'reminders:check-out {--type=all}';

    /**
     * The description of the console command.
     *
     * @var string
     */
    protected $description = 'Send check-out reminders via Telegram (day before and on check-out day)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $type = $this->option('type');

        try {
            if ($type === 'all' || $type === 'day-before') {
                $this->sendDayBeforeReminders();
            }

            if ($type === 'all' || $type === 'checkout-day') {
                $this->sendCheckOutDayReminders();
            }

            $this->info('Check-out reminders sent successfully.');
            return Command::SUCCESS;
        } catch (\Exception $e) {
            Log::error('Error sending check-out reminders', ['error' => $e->getMessage()]);
            $this->error('Error sending check-out reminders: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }

    /**
     * Send day-before check-out reminders
     */
    private function sendDayBeforeReminders(): void
    {
        // Get bookings checking out tomorrow
        $tomorrow = Carbon::tomorrow();
        $bookings = Booking::where('status', 'checked_in')
            ->where('check_out_date', $tomorrow)
            ->with(['user', 'room'])
            ->get();

        $this->info("Sending day-before reminders for {$bookings->count()} bookings...");

        foreach ($bookings as $booking) {
            try {
                // Check if user has Telegram linked
                if (!$booking->user->telegram_user_id) {
                    Log::warning('User has no Telegram ID', ['user_id' => $booking->user->id]);
                    continue;
                }

                $command = new CheckOutReminderCommand(
                    $booking->user->telegram_user_id,
                    $booking->user->id
                );

                $result = $command->sendDayBeforeReminder($booking);

                if ($result['success']) {
                    $this->line("✓ Day-before reminder sent for booking #{$booking->id}");
                } else {
                    $this->warn("✗ Failed to send day-before reminder for booking #{$booking->id}");
                }
            } catch (\Exception $e) {
                Log::error('Error sending day-before reminder', [
                    'booking_id' => $booking->id,
                    'error' => $e->getMessage(),
                ]);
                $this->warn("✗ Error sending day-before reminder for booking #{$booking->id}");
            }
        }
    }

    /**
     * Send check-out day reminders
     */
    private function sendCheckOutDayReminders(): void
    {
        // Get bookings checking out today
        $today = Carbon::today();
        $bookings = Booking::where('status', 'checked_in')
            ->where('check_out_date', $today)
            ->with(['user', 'room'])
            ->get();

        $this->info("Sending check-out day reminders for {$bookings->count()} bookings...");

        foreach ($bookings as $booking) {
            try {
                // Check if user has Telegram linked
                if (!$booking->user->telegram_user_id) {
                    Log::warning('User has no Telegram ID', ['user_id' => $booking->user->id]);
                    continue;
                }

                $command = new CheckOutReminderCommand(
                    $booking->user->telegram_user_id,
                    $booking->user->id
                );

                $result = $command->sendCheckOutDayReminder($booking);

                if ($result['success']) {
                    $this->line("✓ Check-out day reminder sent for booking #{$booking->id}");
                } else {
                    $this->warn("✗ Failed to send check-out day reminder for booking #{$booking->id}");
                }
            } catch (\Exception $e) {
                Log::error('Error sending check-out day reminder', [
                    'booking_id' => $booking->id,
                    'error' => $e->getMessage(),
                ]);
                $this->warn("✗ Error sending check-out day reminder for booking #{$booking->id}");
            }
        }
    }
}
