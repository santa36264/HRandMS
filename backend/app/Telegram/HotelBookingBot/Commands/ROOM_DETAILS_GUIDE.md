# Room Details Command Guide

## Overview
The Room Details feature provides comprehensive information about a specific room when users click "View Details" from search results. It includes full descriptions, amenities, policies, photos, and action buttons.

## Components

### 1. RoomDetailsFormatter
Formats room information for display in Telegram.

**Location:** `app/Telegram/HotelBookingBot/Formatters/RoomDetailsFormatter.php`

**Key Methods:**
- `getDetailedDescription()` - Returns full room description with all details
- `getCompleteMessage()` - Returns message with description + similar rooms suggestion
- `getActionButtons()` - Returns inline keyboard with action buttons
- `getMediaGroup()` - Returns formatted media group for photos
- `getSimilarRoomsSuggestion()` - Returns suggestion text for similar rooms
- `hasPhotos()` - Checks if room has photos
- `getPhotoCount()` - Returns number of photos

**Formatted Output Includes:**
- Room name and type
- Capacity and floor
- Room size
- Full description
- All amenities with emoji icons
- Pricing information (base price, discounts, taxes)
- Policies (cancellation, deposit, check-in/out times, pet, smoking)
- Rating and review count

### 2. RoomDetailsCommand
Handles fetching and processing room details.

**Location:** `app/Telegram/HotelBookingBot/Commands/RoomDetailsCommand.php`

**Key Methods:**
- `getRoomDetails(int $roomId)` - Fetches complete room details
- `getSimilarRooms(int $roomId, int $limit = 3)` - Gets similar rooms by type
- `formatSimilarRooms(array $similarRooms)` - Formats similar rooms for display
- `getSimilarRoomsKeyboard(array $similarRooms)` - Creates keyboard for similar rooms

**Returns:**
```php
[
    'success' => true,
    'message' => 'Formatted room details',
    'buttons' => [...],
    'photos' => [...],
    'has_photos' => true,
    'photo_count' => 3,
]
```

### 3. RoomDetailsCallbackHandler
Processes callbacks from room details interactions.

**Location:** `app/Telegram/HotelBookingBot/Commands/RoomDetailsCallbackHandler.php`

**Handles:**
- `room_details_{roomId}` - View full room details
- `favorite_room_{roomId}` - Add/remove from favorites

**Features:**
- User registration check for favorites
- Toggle favorite status
- Feedback messages

### 4. BaseBot Enhancement
Added `sendMediaGroup()` method to send multiple photos.

**Location:** `app/Telegram/HotelBookingBot/BaseBot.php`

**Method:**
```php
protected function sendMediaGroup(array $mediaGroup): void
```

## Display Format

### Room Details Message
```
🏨 Deluxe Room
━━━━━━━━━━━━━━━━━━━━━━━

Room Information
🏠 Type: Double
🪑 Capacity: 2 guests
📍 Floor: 3
📐 Size: 35 m²

Description
Spacious room with city view, modern furnishings, and premium amenities...

✨ Amenities
📶 WiFi
❄️ AC
📺 TV
🅿️ Parking
🏊 Pool
💪 Gym
🍽️ Restaurant
🍷 Bar

💰 Pricing
Price per night: 150 ETB
Discount: 10% OFF
Discounted price: 135 ETB
Taxes: 15% (20.25 ETB)

📋 Policies
🔄 Cancellation: Free cancellation up to 24 hours before check-in
💳 Deposit: No deposit required
🕐 Check-in: 14:00 | Check-out: 11:00
🐾 Pets: Allowed with additional fee
🚭 Smoking: Non-smoking room

⭐ Rating
Rating: 4.5/5 (28 reviews)

━━━━━━━━━━━━━━━━━━━━━━━
💡 Similar Rooms
Interested in other Double rooms?
Click 'Back to Search' to see more options.
```

### Action Buttons
```
[📅 Book This Room] [⭐ Add to Favorites]
[🔙 Back to Search]
```

### Photos
- Up to 4 photos displayed as media group
- First photo includes room name as caption
- Photos sent before details message

## Callback Data Format

### Room Details
- `room_details_{roomId}` - View full details

### Favorites
- `favorite_room_{roomId}` - Toggle favorite status

### Navigation
- `menu_search_rooms` - Back to search
- `booking_room_{roomId}` - Proceed to booking

## Database Requirements

### Room Model Fields
```php
- id
- name
- type (Single, Double, Suite, etc.)
- capacity
- floor
- size (in m²)
- description
- price_per_night
- discount_percentage
- taxes_percentage
- amenities (JSON array)
- rating
- is_active
- cancellation_policy
- deposit_policy
- check_in_time
- check_out_time
- pet_policy
- smoking_policy
```

### Room Photos
Option 1: `room_photos` table
```php
- id
- room_id
- photo_url
- order
```

Option 2: `photos` JSON field on rooms table
```php
photos: ["url1", "url2", "url3", "url4"]
```

### User Favorites
```php
// Pivot table: room_user (or favorites)
- user_id
- room_id
```

## Usage Flow

1. **User searches for rooms** → Results displayed with formatter
2. **User clicks "View Details"** → `room_details_{roomId}` callback
3. **RoomDetailsCallbackHandler processes** → Fetches room data
4. **Photos sent as media group** → Up to 4 photos
5. **Details message sent** → Full information with buttons
6. **User can:**
   - Click "Book This Room" → Proceed to booking
   - Click "Add to Favorites" → Toggle favorite status
   - Click "Back to Search" → Return to search results

## Amenity Emoji Mapping

```php
'wifi' => '📶'
'ac' => '❄️'
'tv' => '📺'
'parking' => '🅿️'
'pool' => '🏊'
'gym' => '💪'
'restaurant' => '🍽️'
'bar' => '🍷'
'spa' => '💆'
'laundry' => '🧺'
'safe' => '🔒'
'minibar' => '🧊'
'balcony' => '🌅'
'kitchen' => '🍳'
'workspace' => '💼'
'bathtub' => '🛁'
'shower' => '🚿'
'hairdryer' => '💇'
'iron' => '👔'
'telephone' => '☎️'
```

## Error Handling

### Room Not Found
```
❌ This room is no longer available.
```

### Not Registered (for favorites)
```
❌ Please register first to add favorites.

Use the Register button to get started.
```

### General Error
```
❌ Error loading room details. Please try again.
```

## Performance Considerations

- Photos limited to 4 per room
- Amenities displayed with emoji icons
- Similar rooms limited to 3
- Efficient database queries with eager loading
- Cached room data where possible

## Integration Points

### HotelBookingBot
- Handles `room_details_*` callbacks
- Handles `favorite_room_*` callbacks
- Sends media group for photos
- Sends details message with keyboard

### SearchCallbackHandler
- Generates `room_details_{roomId}` callbacks
- Included in search results keyboard

### RoomSearchResultFormatter
- Generates room action buttons
- Links to room details view

## Future Enhancements

1. **Room Reviews** - Display user reviews and ratings
2. **Availability Calendar** - Show availability for selected dates
3. **Room Comparison** - Compare multiple rooms side-by-side
4. **Video Tour** - Send video tour of room
5. **360° Photos** - Interactive room photos
6. **Instant Booking** - Quick booking without additional steps
7. **Price History** - Show price trends
8. **Guest Reviews** - Display guest feedback
