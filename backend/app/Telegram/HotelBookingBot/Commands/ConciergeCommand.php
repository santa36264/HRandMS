<?php

namespace App\Telegram\HotelBookingBot\Commands;

use App\Models\Booking;
use App\Models\ConciergeService;
use App\Models\ConciergeBooking;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ConciergeCommand
{
    private int $chatId;
    private int $userId;
    private string $hotelName = 'SATAAB Hotel';

    public function __construct(int $chatId, int $userId)
    {
        $this->chatId = $chatId;
        $this->userId = $userId;
    }

    /**
     * Show concierge main menu
     */
    public function showMainMenu(): array
    {
        $message = "🎩 <b>Concierge Services</b>\n\n";
        $message .= "Welcome to our concierge service!\n";
        $message .= "Select a service:\n";

        $buttons = [
            [
                ['text' => '✈️ Airport Pickup', 'callback_data' => 'concierge_airport'],
            ],
            [
                ['text' => '🚕 Taxi Service', 'callback_data' => 'concierge_taxi'],
            ],
            [
                ['text' => '🎫 Tour Booking', 'callback_data' => 'concierge_tour'],
            ],
            [
                ['text' => '🍽️ Restaurant', 'callback_data' => 'concierge_food'],
            ],
            [
                ['text' => '💆 Spa Appointment', 'callback_data' => 'concierge_spa'],
            ],
            [
                ['text' => '� Back', 'callback_data' => 'menu_main'],
            ],
        ];

        return [
            'success' => true,
            'message' => $message,
            'buttons' => $buttons,
        ];
    }

    /**
     * Show services by type
     */
    public function showServicesByType(string $type): array
    {
        try {
            $services = ConciergeService::where('type', $type)
                ->where('is_available', true)
                ->get();

            if ($services->isEmpty()) {
                return [
                    'success' => true,
                    'message' => "❌ No services available at the moment.",
                    'buttons' => [[
                        ['text' => '🔙 Back', 'callback_data' => 'concierge_main'],
                    ]],
                ];
            }

            $typeLabel = match ($type) {
                'airport' => '✈️ Airport Pickup',
                'taxi' => '🚕 Taxi Service',
                'tour' => '🎫 Tour Booking',
                'food' => '🍽️ Restaurant',
                'spa' => '� Spa Appointment',
                default => 'Service',
            };

            $message = "<b>{$typeLabel}</b>\n";
            $message .= "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

            $buttons = [];

            foreach ($services as $service) {
                $message .= "<b>{$service->name}</b>\n";
                $message .= "   {$service->description}\n";
                $message .= "   💰 {$service->price} ETB";
                
                if ($service->duration_minutes) {
                    $message .= " | ⏱️ {$service->duration_minutes} min";
                }
                $message .= "\n\n";

                $buttons[] = [
                    ['text' => "Select {$service->name}", 'callback_data' => "concierge_select_{$service->id}"],
                ];
            }

            $buttons[] = [
                ['text' => '🔙 Back', 'callback_data' => 'concierge_main'],
            ];

            return [
                'success' => true,
                'message' => $message,
                'buttons' => $buttons,
            ];
        } catch (\Exception $e) {
            Log::error('Error showing services by type', ['error' => $e->getMessage()]);
            return [
                'success' => false,
                'message' => '❌ Error loading services.',
            ];
        }
    }

    /**
     * Show service details and time slots
     */
    public function showServiceDetails(int $serviceId): array
    {
        try {
            $service = ConciergeService::findOrFail($serviceId);

            $message = "<b>{$service->name}</b>\n";
            $message .= "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";
            $message .= "{$service->description}\n\n";
            $message .= "💰 <b>Price:</b> {$service->price} ETB\n";

            if ($service->duration_minutes) {
                $message .= "⏱️ <b>Duration:</b> {$service->duration_minutes} minutes\n";
            }

            if ($service->provider_name) {
                $message .= "👤 <b>Provider:</b> {$service->provider_name}\n";
            }

            $message .= "\n<b>Select a time slot:</b>\n";

            $buttons = [];
            $timeSlots = $service->time_slots ?? [];

            if (empty($timeSlots)) {
                $timeSlots = $this->getDefaultTimeSlots();
            }

            foreach ($timeSlots as $slot) {
                $buttons[] = [
                    ['text' => $slot, 'callback_data' => "concierge_time_{$serviceId}_{$slot}"],
                ];
            }

            $buttons[] = [
                ['text' => '🔙 Back', 'callback_data' => 'concierge_main'],
            ];

            return [
                'success' => true,
                'message' => $message,
                'buttons' => $buttons,
            ];
        } catch (\Exception $e) {
            Log::error('Error showing service details', ['error' => $e->getMessage()]);
            return [
                'success' => false,
                'message' => '❌ Error loading service details.',
            ];
        }
    }

    /**
     * Confirm booking
     */
    public function confirmBooking(int $serviceId, string $timeSlot, string $specialRequests = ''): array
    {
        try {
            $booking = Booking::where('user_id', $this->userId)
                ->where('status', 'checked_in')
                ->latest()
                ->first();

            if (!$booking) {
                return [
                    'success' => false,
                    'message' => '❌ You must be checked in to book concierge services.',
                ];
            }

            $service = ConciergeService::findOrFail($serviceId);

            $confirmationCode = 'CONC' . strtoupper(Str::random(8));
            $scheduledTime = $this->parseTimeSlot($timeSlot);

            $conciergeBooking = ConciergeBooking::create([
                'booking_id' => $booking->id,
                'user_id' => $this->userId,
                'concierge_service_id' => $serviceId,
                'type' => $service->type,
                'status' => 'confirmed',
                'scheduled_time' => $scheduledTime,
                'special_requests' => $specialRequests,
                'total_amount' => $service->price,
                'confirmation_code' => $confirmationCode,
                'provider_contact' => $service->provider_phone,
            ]);

            // Add to room bill
            $this->addToBill($booking, $service->price, $conciergeBooking->id, $service->name);

            // Notify hotel staff
            $this->notifyHotelStaff($conciergeBooking, $service, $booking);

            return [
                'success' => true,
                'message' => $this->formatConfirmation($conciergeBooking, $service),
                'buttons' => [[
                    ['text' => '🎩 More Services', 'callback_data' => 'concierge_main'],
                    ['text' => '🔙 Back', 'callback_data' => 'menu_main'],
                ]],
                'booking_id' => $conciergeBooking->id,
            ];
        } catch (\Exception $e) {
            Log::error('Error confirming booking', ['error' => $e->getMessage()]);
            return [
                'success' => false,
                'message' => '❌ Error confirming booking.',
            ];
        }
    }

    /**
     * Format confirmation message
     */
    private function formatConfirmation(ConciergeBooking $booking, ConciergeService $service): string
    {
        $message = "✅ <b>Booking Confirmed!</b>\n\n";
        $message .= "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";
        $message .= "📌 <b>Confirmation Code:</b> {$booking->confirmation_code}\n";
        $message .= "🎯 <b>Service:</b> {$service->name}\n";
        $message .= "📅 <b>Scheduled:</b> {$booking->scheduled_time->format('M d, Y H:i')}\n";
        $message .= "💰 <b>Amount:</b> {$booking->total_amount} ETB\n";

        if ($service->provider_name) {
            $message .= "👤 <b>Provider:</b> {$service->provider_name}\n";
        }

        if ($service->provider_phone) {
            $message .= "📞 <b>Contact:</b> {$service->provider_phone}\n";
        }

        if ($booking->special_requests) {
            $message .= "\n📝 <b>Special Requests:</b>\n{$booking->special_requests}\n";
        }

        $message .= "\n✨ Your service will be arranged as scheduled.\n";
        $message .= "Thank you for using {$this->hotelName} Concierge!";

        return $message;
    }

    /**
     * Get default time slots
     */
    private function getDefaultTimeSlots(): array
    {
        $slots = [];
        $now = now();

        for ($i = 0; $i < 8; $i++) {
            $time = $now->addHours($i + 1)->setMinutes(0);
            $slots[] = $time->format('H:i');
        }

        return $slots;
    }

    /**
     * Parse time slot to datetime
     */
    private function parseTimeSlot(string $timeSlot): \DateTime
    {
        $parts = explode(':', $timeSlot);
        $hour = (int)$parts[0];
        $minute = (int)($parts[1] ?? 0);

        return now()->setHour($hour)->setMinutes($minute)->setSeconds(0);
    }

    /**
     * Add to room bill
     */
    private function addToBill(Booking $booking, float $amount, int $conciergeBookingId, string $serviceName): void
    {
        try {
            Log::info('Concierge Service Charge - Add to Bill', [
                'booking_id' => $booking->id,
                'room_id' => $booking->room_id,
                'concierge_booking_id' => $conciergeBookingId,
                'amount' => $amount,
                'description' => "Concierge: {$serviceName}",
            ]);
        } catch (\Exception $e) {
            Log::error('Error adding to bill', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Notify hotel staff
     */
    private function notifyHotelStaff(ConciergeBooking $booking, ConciergeService $service, Booking $hotelBooking): void
    {
        try {
            Log::info('Concierge Booking - Hotel Staff Notification', [
                'booking_id' => $booking->id,
                'confirmation_code' => $booking->confirmation_code,
                'service_type' => $service->type,
                'service_name' => $service->name,
                'guest_name' => $hotelBooking->user->name,
                'room_id' => $hotelBooking->room_id,
                'scheduled_time' => $booking->scheduled_time,
                'total_amount' => $booking->total_amount,
                'special_requests' => $booking->special_requests,
            ]);
        } catch (\Exception $e) {
            Log::error('Error notifying hotel staff', ['error' => $e->getMessage()]);
        }
    }
}
