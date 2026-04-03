<?php

namespace App\Telegram\HotelBookingBot\Commands;

class HelpCommand
{
    /**
     * Get help content
     */
    public static function getHelpContent(): array
    {
        return [
            'commands' => self::getCommandsList(),
            'faq' => self::getFAQ(),
            'contact' => self::getContactInfo(),
            'emergency' => self::getEmergencyContact(),
        ];
    }

    /**
     * Get commands list with descriptions
     */
    private static function getCommandsList(): string
    {
        return "<b>📋 Available Commands</b>\n\n"
            . "<b>/start</b>\n"
            . "   Start the bot and see the main menu\n"
            . "   Example: /start\n\n"
            . "<b>/help</b>\n"
            . "   Show this help message\n"
            . "   Example: /help\n\n"
            . "<b>/rooms</b>\n"
            . "   Browse available rooms\n"
            . "   Example: /rooms\n\n"
            . "<b>/bookings</b>\n"
            . "   View your bookings\n"
            . "   Example: /bookings\n\n"
            . "<b>/cancel</b>\n"
            . "   Cancel a booking\n"
            . "   Example: /cancel\n\n"
            . "<b>Menu Buttons</b>\n"
            . "   🔍 Search Rooms - Find available rooms\n"
            . "   📅 My Bookings - View your reservations\n"
            . "   👤 My Profile - View your account\n"
            . "   📞 Contact Hotel - Get hotel contact info\n"
            . "   ❓ Help - Show this help message\n"
            . "   ⚙️ Settings - Manage preferences";
    }

    /**
     * Get FAQ section
     */
    private static function getFAQ(): string
    {
        return "<b>❓ Frequently Asked Questions</b>\n\n"
            . "<b>Q: How do I book a room?</b>\n"
            . "A: Click '🔍 Search Rooms' button, select your dates, choose a room, and complete the payment.\n\n"
            . "<b>Q: How do I cancel my booking?</b>\n"
            . "A: Go to '📅 My Bookings', select the booking you want to cancel, and click the cancel button. "
            . "Cancellations made 24 hours before check-in are free.\n\n"
            . "<b>Q: What is your refund policy?</b>\n"
            . "A: We offer full refunds for cancellations made at least 24 hours before check-in. "
            . "Cancellations within 24 hours may incur a charge. Contact support for special cases.\n\n"
            . "<b>Q: What are the check-in and check-out times?</b>\n"
            . "A: Standard check-in is at 2:00 PM and check-out is at 11:00 AM. "
            . "Early check-in and late check-out may be available upon request (subject to availability).\n\n"
            . "<b>Q: What amenities are included?</b>\n"
            . "A: All rooms include:\n"
            . "   • Free WiFi\n"
            . "   • Air conditioning\n"
            . "   • Private bathroom\n"
            . "   • Flat-screen TV\n"
            . "   • Daily housekeeping\n"
            . "   • 24/7 room service\n\n"
            . "<b>Q: Can I modify my booking?</b>\n"
            . "A: Yes, you can modify dates or room type up to 48 hours before check-in. "
            . "Contact support for modifications.\n\n"
            . "<b>Q: Do you offer group discounts?</b>\n"
            . "A: Yes! For groups of 10 or more rooms, please contact our sales team for special rates.\n\n"
            . "<b>Q: Is parking available?</b>\n"
            . "A: Yes, we offer complimentary parking for all guests. Parking is available on a first-come, first-served basis.\n\n"
            . "<b>Q: Do you have a restaurant?</b>\n"
            . "A: Yes, our restaurant serves breakfast, lunch, and dinner. Room service is also available 24/7.\n\n"
            . "<b>Q: What payment methods do you accept?</b>\n"
            . "A: We accept credit cards, debit cards, and mobile money (Telebirr, CBE Birr).";
    }

    /**
     * Get contact information
     */
    private static function getContactInfo(): string
    {
        return "<b>📞 Contact Information</b>\n\n"
            . "<b>SATAAB Hotel</b>\n\n"
            . "📱 <b>Phone:</b> +251 911 234 567\n"
            . "📧 <b>Email:</b> info@sataabhotel.com\n"
            . "🌐 <b>Website:</b> www.sataabhotel.com\n"
            . "📍 <b>Address:</b> Addis Ababa, Ethiopia\n\n"
            . "<b>⏰ Working Hours:</b>\n"
            . "Monday - Friday: 8:00 AM - 6:00 PM\n"
            . "Saturday - Sunday: 9:00 AM - 5:00 PM\n\n"
            . "<b>📧 Department Contacts:</b>\n"
            . "Reservations: reservations@sataabhotel.com\n"
            . "Front Desk: frontdesk@sataabhotel.com\n"
            . "Billing: billing@sataabhotel.com\n"
            . "Complaints: complaints@sataabhotel.com\n\n"
            . "<b>💬 Live Chat:</b>\n"
            . "Available on our website during business hours";
    }

    /**
     * Get emergency contact
     */
    private static function getEmergencyContact(): string
    {
        return "<b>🚨 Emergency Contacts</b>\n\n"
            . "<b>Hotel Emergency Line:</b>\n"
            . "📱 +251 911 234 567 (24/7)\n\n"
            . "<b>Local Emergency Services:</b>\n"
            . "🚑 Ambulance: 911\n"
            . "🚒 Fire: 911\n"
            . "🚔 Police: 911\n\n"
            . "<b>Nearby Hospitals:</b>\n"
            . "• St. Paul's Hospital Millennium Medical College\n"
            . "  📍 Addis Ababa\n"
            . "  📱 +251 116 189 000\n\n"
            . "• Tikur Anbessa Specialized Hospital\n"
            . "  📍 Addis Ababa\n"
            . "  📱 +251 111 550 011\n\n"
            . "<b>Important:</b>\n"
            . "In case of emergency, always call 911 first, then notify the front desk.";
    }

    /**
     * Get help keyboard
     */
    public static function getHelpKeyboard(): array
    {
        return [
            [
                ['text' => '📋 Commands', 'callback_data' => 'help_commands'],
                ['text' => '❓ FAQ', 'callback_data' => 'help_faq'],
            ],
            [
                ['text' => '📞 Contact', 'callback_data' => 'help_contact'],
                ['text' => '🚨 Emergency', 'callback_data' => 'help_emergency'],
            ],
            [
                ['text' => '⬅️ Back', 'callback_data' => 'menu_back'],
            ]
        ];
    }

    /**
     * Get main help message
     */
    public static function getMainHelpMessage(): string
    {
        return "<b>❓ Help & Support</b>\n\n"
            . "Welcome to SATAAB Hotel Bot Help Center!\n\n"
            . "Select what you need help with:\n\n"
            . "📋 <b>Commands</b> - Learn about all available commands\n"
            . "❓ <b>FAQ</b> - Find answers to common questions\n"
            . "📞 <b>Contact</b> - Get our contact information\n"
            . "🚨 <b>Emergency</b> - Emergency contact numbers\n\n"
            . "Can't find what you're looking for? Contact our support team!";
    }
}
