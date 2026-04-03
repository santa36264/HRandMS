# Booking Flow Guide

## Overview
Complete implementation of the multi-step booking flow for the Telegram hotel bot. Users can book rooms with guest information, special requests, and confirmation.

## Components

### 1. BookingStateManager
Manages booking state and data with 30-minute expiration.

**Location:** `app/Telegram/HotelBookingBot/Commands/BookingStateManager.php`

**Key Methods:**
- `getState()` - Get current booking state
- `setState(string $state)` - Set booking state
- `getBookingData()` - Get all booking data
- `setBookingData(array $data)` - Set booking data
- `updateBookingData(array $data)` - Update specific booking data
- `getData(string $key, $default)` - Get specific data value
- `clearState()` - Clear booking state and data
- `isBookingInProgress()` - Check if booking is active
- `getBookingSummary()` - Get formatted booking summary
- `getTimeRemaining()` - Get remaining time before expiration
- `isExpired()` - Check if session expired

**Cache Expiration:** 30 minutes (1800 seconds)

**Stored Data:**
```php
[
    'room_id' => int,
    'room_name' => string,
    'room_type' => string,
    'room_capacity' => int,
    'price_per_night' => float,
    'check_in_date' => string (Y-m-d),
    'check_out_date' => string (Y-m-d),
    'nights' => int,
    'total_price' => float,
    'guest_count' => int,
    'guest_names' => array,
    'special_requests' => string,
]
```

### 2. BookingCommand
Handles booking process logic.

**Location:** `app/Telegram/HotelBookingBot/Commands/BookingCommand.php`

**Key Methods:**
- `startBooking(int $roomId, ?string $checkInDate, ?string $checkOutDate, ?int $guestCount)` - Start booking
- `handleGuestCount(int $guestCount)` - Process guest count
- `handleGuestName(string $name)` - Process guest name
- `handleSpecialRequests(string $requests)` - Process special requests
- `getBookingSummary()` - Get booking summary
- `getConfirmationButtons()` - Get confirmation buttons
- `confirmBooking()` - Confirm and create booking
- `cancelBooking()` - Cancel booking

### 3. BookingCallbackHandler
Processes booking-related callbacks.

**Location:** `app/Telegram/HotelBookingBot/Commands/BookingCallbackHandler.php`

**Handles:**
- `booking_confirm` - Confirm booking
- `booking_edit` - Edit booking details
- `booking_cancel` - Cancel booking

## Booking Flow Steps

### Step 1: Start Booking
```
User clicks "📅 Book This Room" from room details
    ↓
booking_room_{roomId} callback triggered
    ↓
BookingCommand.startBooking() called
    ↓
Room data stored in cache
    ↓
If guest count not provided → Ask for guest count
If guest count provided → Ask for guest names
```

### Step 2: Guest Count
```
Message: "👥 How many guests will be staying?"
Input: User enters number (1 to room capacity)
Validation: Must be between 1 and room capacity
Next: Ask for guest names
```

### Step 3: Guest Names
```
Message: "👤 Please enter the name of guest 1"
Input: User enters guest name
Validation: 2-100 characters
Loop: Repeat for each guest
Next: Ask for special requests after all names entered
```

### Step 4: Special Requests
```
Message: "Do you have any special requests?"
Input: User enters requests or "skip"
Validation: Max 500 characters
Next: Show booking summary
```

### Step 5: Booking Summary
```
Display:
- Room name and type
- Check-in/out dates
- Number of nights
- Total price
- Guest names
- Special requests (if any)

Buttons:
[✅ Confirm Booking] [✏️ Edit Details]
[❌ Cancel]
```

### Step 6: Confirmation
```
User clicks "✅ Confirm Booking"
    ↓
Booking created in database
    ↓
Confirmation message with booking reference
    ↓
State cleared
```

## Display Examples

### Guest Count Request
```
👥 How many guests will be staying?

Maximum capacity: 2 guests
Please enter a number between 1 and 2
```

### Guest Names Request
```
👤 Guest Names

Please enter the name of guest 1 (primary guest):

Guest 1 of 2
```

### Special Requests
```
✅ All guest names recorded!

Special Requests (Optional)

Do you have any special requests for your stay?
(e.g., high floor, late check-in, extra pillows)

Type your request or 'skip' to continue:
```

### Booking Summary
```
📋 Booking Summary

🏨 Room: Deluxe Room
🏠 Type: Double
📅 Check-in: 2024-03-25
📅 Check-out: 2024-03-28
🌙 Nights: 3
👥 Guests: 2
💰 Total: 450 ETB

Guest Names:
  1. John Doe
  2. Jane Doe

Special Requests:
High floor preferred, late check-in needed
```

### Confirmation Buttons
```
[✅ Confirm Booking] [✏️ Edit Details]
[❌ Cancel]
```

### Success Message
```
✅ Booking Confirmed!

Your booking has been confirmed.
Booking reference: #A1B2C3D4

A confirmation email has been sent to your registered email address.

Thank you for choosing SATAAB Hotel! 🏨
```

## Callback Data Format

### Start Booking
- `booking_room_{roomId}` - Start booking for room

### Confirmation Actions
- `booking_confirm` - Confirm booking
- `booking_edit` - Edit booking details
- `booking_cancel` - Cancel booking

## State Transitions

```
Initial State: null
    ↓
awaiting_guest_count (if not provided)
    ↓
awaiting_guest_names (for each guest)
    ↓
awaiting_special_requests
    ↓
awaiting_confirmation
    ↓
null (after confirmation/cancellation)
```

## Error Handling

### Invalid Guest Count
```
❌ Invalid guest count. Please enter a number between 1 and {capacity}.
```

### Invalid Guest Name
```
❌ Name must be between 2 and 100 characters.
```

### Invalid Special Requests
```
❌ Special requests must be 500 characters or less.
```

### Session Expired
```
❌ Booking session expired. Please start over.
```

### Room Not Available
```
❌ This room is no longer available.
```

## Data Validation

### Guest Count
- Minimum: 1
- Maximum: Room capacity
- Type: Integer

### Guest Names
- Minimum length: 2 characters
- Maximum length: 100 characters
- Type: String

### Special Requests
- Maximum length: 500 characters
- Type: String
- Optional (can be "skip")

## Cache Management

### Expiration
- Duration: 30 minutes (1800 seconds)
- Auto-refresh: Each update extends expiration
- Cleanup: Automatic by Laravel cache

### Storage
- Driver: Configured in `config/cache.php`
- Key format: `telegram_booking_{user_id}_{suffix}`
- Suffixes: `state`, `data`

## Integration Points

### HotelBookingBot
- Handles `booking_room_*` callbacks
- Handles `booking_confirm/edit/cancel` callbacks
- Processes guest count messages
- Processes guest name messages
- Processes special request messages

### Room Details
- "📅 Book This Room" button triggers booking
- Callback: `booking_room_{roomId}`

### Search Results
- "📅 Book" button in room cards
- Callback: `booking_room_{roomId}`

## Message Flow

```
User Input → HotelBookingBot.handleMessage()
    ↓
Check booking state
    ↓
Route to appropriate handler:
  - handleBookingGuestCount()
  - handleBookingGuestName()
  - handleBookingSpecialRequests()
    ↓
BookingCommand processes input
    ↓
BookingStateManager updates state
    ↓
Response sent to user
```

## Callback Flow

```
User clicks button → HotelBookingBot.handleCallbackQuery()
    ↓
Check callback data prefix
    ↓
Route to handleBookingCallbackQuery()
    ↓
BookingCallbackHandler processes callback
    ↓
BookingCommand executes action
    ↓
Response sent to user
```

## Database Integration

### Future: Bookings Table
```sql
CREATE TABLE bookings (
    id BIGINT PRIMARY KEY,
    user_id BIGINT NOT NULL,
    room_id BIGINT NOT NULL,
    check_in_date DATE NOT NULL,
    check_out_date DATE NOT NULL,
    guest_count INT NOT NULL,
    guest_names JSON NOT NULL,
    special_requests TEXT NULL,
    total_price DECIMAL(10, 2) NOT NULL,
    status VARCHAR(50) DEFAULT 'pending',
    booking_reference VARCHAR(50) UNIQUE,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (room_id) REFERENCES rooms(id)
);
```

## Performance Considerations

- Cache-based state management (no database queries during flow)
- 30-minute expiration prevents stale bookings
- Efficient data updates
- Minimal memory footprint
- Fast state transitions

## Security Features

- User ID validation
- Room availability check
- Guest count validation
- Input sanitization
- Session expiration
- Automatic cleanup

## Future Enhancements

1. **Payment Integration** - Process payment before confirmation
2. **Email Confirmation** - Send booking confirmation email
3. **SMS Notification** - Send SMS with booking details
4. **Booking Modifications** - Allow editing after confirmation
5. **Cancellation Policy** - Apply cancellation rules
6. **Deposit Handling** - Collect deposit if required
7. **Booking History** - Store and retrieve past bookings
8. **Booking Status** - Track booking status (pending, confirmed, checked-in, completed)
9. **Guest Preferences** - Save guest preferences for future bookings
10. **Loyalty Points** - Award points for bookings

## Troubleshooting

### Session Expired
- User took too long (>30 minutes)
- Solution: Start booking again

### Invalid Input
- Check input format and length
- Verify guest count is within capacity
- Ensure names are 2-100 characters

### Booking Not Confirmed
- Check if all steps completed
- Verify special requests under 500 characters
- Ensure user clicked confirm button

## Testing Checklist

- [ ] Start booking from room details
- [ ] Enter valid guest count
- [ ] Enter guest names for each guest
- [ ] Enter special requests
- [ ] View booking summary
- [ ] Confirm booking
- [ ] Edit booking details
- [ ] Cancel booking
- [ ] Test session expiration (>30 min)
- [ ] Test invalid inputs
- [ ] Test room capacity limits
