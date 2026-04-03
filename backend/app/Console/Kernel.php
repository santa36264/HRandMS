<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use Illuminate\Support\Facades\Log;

class Kernel extends ConsoleKernel
{
    protected function schedule(Schedule $schedule): void
    {
        /**
         * ── 24-hour check-in reminders ────────────────────────────────
         * Runs daily at 10:00 AM.
         * Sends reminders to users checking in tomorrow.
         */
        $schedule->command('reminders:check-in --type=24h')
                 ->dailyAt('10:00')
                 ->withoutOverlapping(10)
                 ->runInBackground()
                 ->appendOutputTo(storage_path('logs/scheduler.log'))
                 ->onFailure(function () {
                     Log::error('Scheduler: reminders:check-in 24h failed.');
                 });

        /**
         * ── 2-hour check-in reminders ─────────────────────────────────
         * Runs every hour at :00 minutes.
         * Sends reminders to users checking in within 2 hours.
         */
        $schedule->command('reminders:check-in --type=2h')
                 ->hourlyAt(0)
                 ->withoutOverlapping(10)
                 ->runInBackground()
                 ->appendOutputTo(storage_path('logs/scheduler.log'))
                 ->onFailure(function () {
                     Log::error('Scheduler: reminders:check-in 2h failed.');
                 });

        /**
         * ── Day-before check-out reminders ────────────────────────────
         * Runs daily at 09:00 AM.
         * Sends reminders to users checking out tomorrow.
         */
        $schedule->command('reminders:check-out --type=day-before')
                 ->dailyAt('09:00')
                 ->withoutOverlapping(10)
                 ->runInBackground()
                 ->appendOutputTo(storage_path('logs/scheduler.log'))
                 ->onFailure(function () {
                     Log::error('Scheduler: reminders:check-out day-before failed.');
                 });

        /**
         * ── Check-out day reminders ───────────────────────────────────
         * Runs daily at 08:00 AM.
         * Sends reminders to users checking out today.
         */
        $schedule->command('reminders:check-out --type=checkout-day')
                 ->dailyAt('08:00')
                 ->withoutOverlapping(10)
                 ->runInBackground()
                 ->appendOutputTo(storage_path('logs/scheduler.log'))
                 ->onFailure(function () {
                     Log::error('Scheduler: reminders:check-out checkout-day failed.');
                 });

        /**
         * ── Check-in reminders ────────────────────────────────────────
         * Runs daily at 10:00 AM.
         * Dispatches SendCheckInReminderJob for every confirmed booking
         * whose check_in_date is tomorrow.
         */
        $schedule->command('notifications:checkin-reminders --queue=reminders')
                 ->dailyAt('10:00')
                 ->withoutOverlapping(10)          // lock for max 10 min
                 ->runInBackground()
                 ->appendOutputTo(storage_path('logs/scheduler.log'))
                 ->onFailure(function () {
                     Log::error('Scheduler: notifications:checkin-reminders failed.');
                 });

        /**
         * ── Review requests ───────────────────────────────────────────
         * Runs daily at 11:00 AM.
         * Dispatches SendReviewRequestJob for every checked-out booking
         * from yesterday that has no review yet.
         */
        $schedule->command('notifications:review-requests --queue=reminders')
                 ->dailyAt('11:00')
                 ->withoutOverlapping(10)
                 ->runInBackground()
                 ->appendOutputTo(storage_path('logs/scheduler.log'))
                 ->onFailure(function () {
                     Log::error('Scheduler: notifications:review-requests failed.');
                 });

        /**
         * ── Prune failed jobs older than 7 days ───────────────────────
         * Keeps the failed_jobs table clean.
         */
        $schedule->command('queue:prune-failed --hours=168')
                 ->weekly()
                 ->runInBackground();

        /**
         * ── Concierge service reminders ────────────────────────────────
         * Runs every hour at :00 minutes.
         * Sends 24-hour and 2-hour reminders for upcoming concierge services.
         */
        $schedule->command('concierge:send-reminders')
                 ->hourlyAt(0)
                 ->withoutOverlapping(10)
                 ->runInBackground()
                 ->appendOutputTo(storage_path('logs/scheduler.log'))
                 ->onFailure(function () {
                     Log::error('Scheduler: concierge:send-reminders failed.');
                 });

        /**
         * ── Daily operations summary ───────────────────────────────────
         * Runs daily at 07:00 AM.
         * Sends summary of check-ins, check-outs, occupancy, and pending requests.
         */
        $schedule->command('staff:daily-summary')
                 ->dailyAt('07:00')
                 ->withoutOverlapping(10)
                 ->runInBackground()
                 ->appendOutputTo(storage_path('logs/scheduler.log'))
                 ->onFailure(function () {
                     Log::error('Scheduler: staff:daily-summary failed.');
                 });

        /**
         * ── Review requests after check-out ────────────────────────────
         * Runs every 30 minutes.
         * Sends review requests to guests who checked out in the last 2 hours.
         */
        $schedule->command('reviews:send-requests')
                 ->everyThirtyMinutes()
                 ->withoutOverlapping(10)
                 ->runInBackground()
                 ->appendOutputTo(storage_path('logs/scheduler.log'))
                 ->onFailure(function () {
                     Log::error('Scheduler: reviews:send-requests failed.');
                 });

        /**
         * ── Generate review analytics ──────────────────────────────────
         * Runs daily at 23:00 (11 PM).
         * Generates daily analytics, updates room ratings, and checks for negative reviews.
         */
        $schedule->command('analytics:generate-reviews')
                 ->dailyAt('23:00')
                 ->withoutOverlapping(10)
                 ->runInBackground()
                 ->appendOutputTo(storage_path('logs/scheduler.log'))
                 ->onFailure(function () {
                     Log::error('Scheduler: analytics:generate-reviews failed.');
                 });
    }

    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');
        require base_path('routes/console.php');
    }
}
