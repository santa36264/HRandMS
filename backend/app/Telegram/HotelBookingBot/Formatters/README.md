# Room Search Result Formatter

## Overview
The `RoomSearchResultFormatter` class provides a comprehensive formatting system for displaying room search results in Telegram with pagination, sorting, and detailed room information.

## Features

### 1. Room Display
Each room is displayed with:
- 🏨 Room type (Single, Double, Suite, etc.)
- Room name
- 💰 Price per night
- 🪑 Maximum capacity
- 📝 Short description (truncated to 60 chars)
- ✨ Amenity badges with emojis
- 💵 Total price calculation for stay duration
- ⭐ Rating out of 5

### 2. Amenity Badges
Amenities are displayed with emoji indicators:
- 📶 WiFi
- ❄️ AC
- 📺 TV
- 🅿️ Parking
- 🏊 Pool
- 💪 Gym
- 🍽️ Restaurant
- 🍷 Bar
- 💆 Spa
- 🧺 Laundry
- 🔒 Safe
- 🧊 Minibar

### 3. Pagination
- Shows 5 rooms per page
- Previous/Next buttons for navigation
- Page indicator (e.g., "Page 1/3")
- Automatically hidden if only 1 page

### 4. Sorting Options
- **Sort by Price**: Ascending order (cheapest first)
- **Sort by Capacity**: Descending order (largest first)
- Current sort is indicated with ✓

### 5. Price Summary
Displays:
- Minimum and maximum price per night
- Total price range for the entire stay
- Number of nights

## Usage

### Basic Usage
```php
use App\Telegram\HotelBookingBot\Formatters\RoomSearchResultFormatter;
use Carbon\Carbon;

$rooms = [
    [
        'id' => 1,
        'name' => 'Deluxe Room',
        'type' => 'Double',
        'capacity' => 2,
        'price' => 150,
        'rating' => 4.5,
        'description' => 'Spacious room with city view',
        'amenities' => ['WiFi', 'AC', 'TV', 'Safe']
    ],
    // ... more rooms
];

$checkIn = Carbon::parse('2024-03-25');
$checkOut = Carbon::parse('2024-03-28');
$guestCount = 2;

$formatter = new RoomSearchResultFormatter(
    $rooms,
    $checkIn,
    $checkOut,
    $guestCount
);

// Get formatted message
$message = $formatter->getFormattedMessage();

// Get keyboard with buttons
$keyboard = $formatter->getKeyboard();

// Get price summary
$summary = $formatter->getSummary();
```

### With Pagination
```php
$formatter = new RoomSearchResultFormatter(
    $rooms,
    $checkIn,
    $checkOut,
    $guestCount,
    $currentPage = 2  // Show page 2
);
```

### With Sorting
```php
$formatter = new RoomSearchResultFormatter(
    $rooms,
    $checkIn,
    $checkOut,
    $guestCount,
    $currentPage = 1,
    $sortBy = 'capacity'  // Sort by capacity instead of price
);
```

### Fluent Interface
```php
$formatter = new RoomSearchResultFormatter($rooms, $checkIn, $checkOut, $guestCount)
    ->setCurrentPage(2)
    ->setSortBy('capacity');
```

## Methods

### Public Methods

#### `getFormattedMessage(): string`
Returns the formatted message with all room details.

#### `getKeyboard(): array`
Returns the inline keyboard with:
- Room action buttons (View Details, Book Now)
- Pagination buttons (if applicable)
- Sort buttons
- Back button

#### `getSummary(): string`
Returns price range summary for the search.

#### `setCurrentPage(int $page): self`
Set the current page for pagination.

#### `setSortBy(string $sortBy): self`
Set sort order ('price' or 'capacity').

#### `getTotalRooms(): int`
Get total number of rooms in search results.

#### `getTotalPages(): int`
Get total number of pages.

#### `getCurrentPage(): int`
Get current page number.

#### `getSortBy(): string`
Get current sort order.

## Callback Data Format

### Room Actions
- `room_details_{roomId}` - View full details
- `booking_room_{roomId}` - Proceed to booking

### Pagination
- `search_page_{pageNumber}` - Navigate to page

### Sorting
- `search_sort_price` - Sort by price
- `search_sort_capacity` - Sort by capacity

### Navigation
- `menu_search_rooms` - New search
- `menu_back` - Back to menu

## Example Output

```
✅ Available Rooms

📅 Mar 25 - Mar 28, 2024 (3 nights)
👥 2 guests
━━━━━━━━━━━━━━━━━━━━━━━

1. 🏨 Double
   Deluxe Room
   💰 150 ETB/night
   🪑 Max 2 guests
   📝 Spacious room with city view...
   ✨ 📶 WiFi | ❄️ AC | 📺 TV | 🔒 Safe
   💵 Total: 450 ETB (3 nights)
   ⭐ Rating: 4.5/5

2. 🏨 Suite
   Presidential Suite
   💰 250 ETB/night
   🪑 Max 4 guests
   📝 Luxury suite with panoramic view...
   ✨ 📶 WiFi | ❄️ AC | 🏊 Pool | 🍽️ Restaurant
   💵 Total: 750 ETB (3 nights)
   ⭐ Rating: 5/5

💡 Price Range: 150 - 250 ETB/night
💵 Total for 3 nights: 450 - 750 ETB
```

## Integration with SearchCallbackHandler

The formatter is automatically used in the search flow:

1. User completes search criteria
2. `RoomSearchCommand` retrieves available rooms
3. `RoomSearchResultFormatter` formats the results
4. Results are displayed with pagination and sorting options
5. User can navigate pages or change sort order
6. `SearchCallbackHandler` handles pagination/sorting callbacks
7. Formatter re-renders with new page/sort

## State Management

Search results are stored in cache via `SearchStateManager`:
- Results persist for 2 hours
- Pagination state is maintained
- Sort preference is remembered
- User can navigate back to previous pages

## Performance Considerations

- Pagination limits display to 5 rooms per page
- Amenities limited to 4 per room (with "+X more" indicator)
- Descriptions truncated to 60 characters
- Efficient array slicing for pagination
- Minimal string operations for formatting
