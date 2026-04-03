# Test Suite Overview

Comprehensive test suite for SATAAB Hotel Telegram Bot with 50+ tests covering webhooks, commands, conversations, payments, and notifications.

## Quick Start

```bash
# Run all tests
php artisan test

# Run with coverage
php artisan test --coverage

# Run specific test file
php artisan test tests/Feature/Telegram/WebhookHandlerTest.php

# Run specific test method
php artisan test --filter=test_webhook_processes_message_update
```

## Test Categories

### Feature Tests (Integration)
Tests that verify complete workflows and user interactions:
- Webhook message/callback handling
- Command responses
- Multi-step conversation flows
- Payment integration
- Notification delivery
- End-to-end user journeys

### Unit Tests
Tests that verify individual components:
- Language service
- Language command
- Admin command handler
- Telegram API mocking
- Staff notification service
- Payment gateway mocking

## Test Files

```
tests/
├── Feature/
│   └── Telegram/
│       ├── WebhookHandlerTest.php          (5 tests)
│       ├── CommandResponseTest.php         (10 tests)
│       ├── ConversationFlowTest.php        (7 tests)
│       ├── PaymentIntegrationTest.php      (6 tests)
│       ├── NotificationSendingTest.php     (9 tests)
│       └── IntegrationTest.php             (6 tests)
├── Unit/
│   ├── Telegram/
│   │   ├── LanguageServiceTest.php         (11 tests)
│   │   ├── LanguageCommandTest.php         (5 tests)
│   │   ├── AdminCommandHandlerTest.php     (6 tests)
│   │   └── TelegramApiMockTest.php         (9 tests)
│   ├── Services/
│   │   └── StaffNotificationServiceTest.php (6 tests)
│   └── Payments/
│       └── PaymentGatewayMockTest.php      (9 tests)
├── Helpers/
│   └── TelegramTestHelper.php              (Utility functions)
├── TestCase.php                            (Base test class)
└── CreatesApplication.php                  (Application factory)
```

## Test Statistics

- **Total Tests**: 50+
- **Feature Tests**: 43
- **Unit Tests**: 56
- **Coverage**: Core bot functionality
- **Runtime**: < 30 seconds
- **Database**: In-memory SQLite

## Key Features

✅ **Webhook Testing**
- Message update processing
- Callback query handling
- Invalid update handling
- Update logging

✅ **Command Testing**
- All 10+ commands tested
- Response validation
- Error handling

✅ **Conversation Flow Testing**
- Registration flow
- Booking flow
- Search flow
- Language selection
- Room service ordering
- Concierge booking

✅ **Payment Testing**
- Payment callbacks
- Gateway integration
- Refund handling
- Error scenarios

✅ **Notification Testing**
- Booking confirmations
- Check-in reminders
- Payment receipts
- Review requests
- Staff notifications

✅ **API Mocking**
- Telegram API mocking
- Payment gateway mocking
- Error response handling
- Timeout simulation

✅ **Multi-User Testing**
- Concurrent user interactions
- Data isolation
- Language preferences

## Running Tests

### All Tests
```bash
php artisan test
```

### Feature Tests Only
```bash
php artisan test tests/Feature
```

### Unit Tests Only
```bash
php artisan test tests/Unit
```

### Specific Test Suite
```bash
php artisan test tests/Feature/Telegram/WebhookHandlerTest.php
```

### Specific Test Method
```bash
php artisan test --filter=test_webhook_processes_message_update
```

### With Coverage Report
```bash
php artisan test --coverage
```

### With HTML Coverage Report
```bash
php artisan test --coverage-html=coverage
```

### Verbose Output
```bash
php artisan test --verbose
```

### Stop on First Failure
```bash
php artisan test --stop-on-failure
```

## Test Helpers

The `TelegramTestHelper` class provides utilities:

```php
// Create message update
$update = TelegramTestHelper::createMessageUpdate('/start');

// Create callback update
$update = TelegramTestHelper::createCallbackUpdate('language_en');

// Create test user
$user = TelegramTestHelper::createTelegramUser();

// Create test booking
$booking = TelegramTestHelper::createTestBooking();

// Create test payment
$payment = TelegramTestHelper::createTestPayment();

// Create room service order
$order = TelegramTestHelper::createTestRoomServiceOrder();

// Create concierge booking
$concierge = TelegramTestHelper::createTestConciergeBooking();

// Assert webhook success
TelegramTestHelper::assertWebhookSuccess($response);

// Assert webhook error
TelegramTestHelper::assertWebhookError($response);
```

## Example Test

```php
public function test_webhook_processes_message_update(): void
{
    $update = [
        'update_id' => 123456789,
        'message' => [
            'message_id' => 1,
            'date' => time(),
            'chat' => ['id' => 987654321, 'type' => 'private'],
            'from' => [
                'id' => 987654321,
                'is_bot' => false,
                'first_name' => 'John',
                'username' => 'johndoe',
                'language_code' => 'en',
            ],
            'text' => '/start',
        ],
    ];

    $response = $this->postJson('/api/telegram-webhook', $update);

    $response->assertStatus(200);
    $response->assertJson(['ok' => true]);
}
```

## Mocking APIs

### Telegram API
```php
Http::fake([
    'https://api.telegram.org/bot*' => Http::response([
        'ok' => true,
        'result' => [...],
    ]),
]);
```

### Payment Gateway
```php
Http::fake([
    'https://api.chapa.co/v1/*' => Http::response([
        'status' => 'success',
        'data' => [...],
    ]),
]);
```

## Database

Tests use in-memory SQLite database for speed:
- No file I/O
- Automatic cleanup
- Isolated test state
- Fast execution

## CI/CD Integration

```bash
# Install dependencies
composer install

# Run tests with coverage
php artisan test --coverage --min=80

# Generate coverage report
php artisan test --coverage-html=coverage

# Run with parallel execution
php artisan test --parallel
```

## Debugging

### Enable Query Logging
```php
DB::enableQueryLog();
// ... test code ...
dd(DB::getQueryLog());
```

### Dump Response
```php
$response->dump();
```

### Assert Database State
```php
$this->assertDatabaseHas('users', ['email' => 'test@example.com']);
$this->assertDatabaseMissing('users', ['email' => 'invalid@example.com']);
```

### Use Tinker
```bash
php artisan tinker
```

## Best Practices

1. ✅ Use `RefreshDatabase` trait for clean state
2. ✅ Use factories for test data
3. ✅ Test one behavior per test
4. ✅ Use descriptive test names
5. ✅ Mock external services
6. ✅ Use test helpers to reduce duplication
7. ✅ Test edge cases and errors
8. ✅ Keep tests fast and isolated

## Troubleshooting

### Tests Fail with Database Error
- Check `phpunit.xml` has `DB_DATABASE` set to `:memory:`
- Ensure `RefreshDatabase` trait is used

### Tests Timeout
- Check for infinite loops
- Use `Http::preventStrayRequests()` to catch unmocked requests

### Mock Not Working
- Verify mock URL matches exactly
- Check request method (GET vs POST)
- Ensure `Http::fake()` is called before request

### Database State Issues
- Ensure `RefreshDatabase` trait is used
- Check for test interdependencies
- Use factories instead of manual creation

## Performance

- **Total Runtime**: < 30 seconds
- **Database**: In-memory SQLite (fast)
- **API Calls**: All mocked (no network)
- **Parallel Execution**: Supported

## Coverage

Current coverage includes:
- ✅ Webhook handling
- ✅ Command processing
- ✅ Conversation flows
- ✅ Payment integration
- ✅ Notification delivery
- ✅ Language support
- ✅ Admin commands
- ✅ API mocking
- ✅ Error handling
- ✅ Multi-user scenarios

## Next Steps

1. Run tests: `php artisan test`
2. Check coverage: `php artisan test --coverage`
3. Add more tests as features are added
4. Integrate with CI/CD pipeline
5. Monitor test performance

## Support

For issues or questions about tests:
1. Check TESTING_GUIDE.md for detailed documentation
2. Review test examples in test files
3. Use test helpers for common operations
4. Check Laravel testing documentation
