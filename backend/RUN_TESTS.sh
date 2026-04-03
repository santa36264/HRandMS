#!/bin/bash

# SATAAB Hotel Telegram Bot - Test Runner Script
# This script provides convenient commands to run the test suite

set -e

# Colors for output
GREEN='\033[0;32m'
BLUE='\033[0;34m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Function to print colored output
print_header() {
    echo -e "${BLUE}========================================${NC}"
    echo -e "${BLUE}$1${NC}"
    echo -e "${BLUE}========================================${NC}"
}

print_success() {
    echo -e "${GREEN}✓ $1${NC}"
}

print_info() {
    echo -e "${YELLOW}ℹ $1${NC}"
}

# Main menu
if [ $# -eq 0 ]; then
    print_header "SATAAB Hotel Telegram Bot - Test Suite"
    echo ""
    echo "Usage: ./RUN_TESTS.sh [command]"
    echo ""
    echo "Commands:"
    echo "  all              Run all tests"
    echo "  feature          Run feature tests only"
    echo "  unit             Run unit tests only"
    echo "  coverage         Run tests with coverage report"
    echo "  coverage-html    Generate HTML coverage report"
    echo "  webhook          Run webhook tests"
    echo "  commands         Run command response tests"
    echo "  flows            Run conversation flow tests"
    echo "  payments         Run payment integration tests"
    echo "  notifications    Run notification tests"
    echo "  integration      Run integration tests"
    echo "  language         Run language service tests"
    echo "  admin            Run admin command tests"
    echo "  api-mock         Run Telegram API mock tests"
    echo "  payment-mock     Run payment gateway mock tests"
    echo "  verbose          Run all tests with verbose output"
    echo "  stop-on-fail     Run tests and stop on first failure"
    echo "  parallel         Run tests in parallel"
    echo "  help             Show this help message"
    echo ""
    exit 0
fi

# Run commands
case "$1" in
    all)
        print_header "Running All Tests"
        php artisan test
        print_success "All tests completed"
        ;;
    feature)
        print_header "Running Feature Tests"
        php artisan test tests/Feature
        print_success "Feature tests completed"
        ;;
    unit)
        print_header "Running Unit Tests"
        php artisan test tests/Unit
        print_success "Unit tests completed"
        ;;
    coverage)
        print_header "Running Tests with Coverage"
        php artisan test --coverage
        print_success "Coverage report generated"
        ;;
    coverage-html)
        print_header "Generating HTML Coverage Report"
        php artisan test --coverage-html=coverage
        print_success "HTML coverage report generated in coverage/index.html"
        ;;
    webhook)
        print_header "Running Webhook Tests"
        php artisan test tests/Feature/Telegram/WebhookHandlerTest.php
        print_success "Webhook tests completed"
        ;;
    commands)
        print_header "Running Command Response Tests"
        php artisan test tests/Feature/Telegram/CommandResponseTest.php
        print_success "Command response tests completed"
        ;;
    flows)
        print_header "Running Conversation Flow Tests"
        php artisan test tests/Feature/Telegram/ConversationFlowTest.php
        print_success "Conversation flow tests completed"
        ;;
    payments)
        print_header "Running Payment Integration Tests"
        php artisan test tests/Feature/Telegram/PaymentIntegrationTest.php
        print_success "Payment integration tests completed"
        ;;
    notifications)
        print_header "Running Notification Tests"
        php artisan test tests/Feature/Telegram/NotificationSendingTest.php
        print_success "Notification tests completed"
        ;;
    integration)
        print_header "Running Integration Tests"
        php artisan test tests/Feature/Telegram/IntegrationTest.php
        print_success "Integration tests completed"
        ;;
    language)
        print_header "Running Language Service Tests"
        php artisan test tests/Unit/Telegram/LanguageServiceTest.php
        print_success "Language service tests completed"
        ;;
    admin)
        print_header "Running Admin Command Tests"
        php artisan test tests/Unit/Telegram/AdminCommandHandlerTest.php
        print_success "Admin command tests completed"
        ;;
    api-mock)
        print_header "Running Telegram API Mock Tests"
        php artisan test tests/Unit/Telegram/TelegramApiMockTest.php
        print_success "Telegram API mock tests completed"
        ;;
    payment-mock)
        print_header "Running Payment Gateway Mock Tests"
        php artisan test tests/Unit/Payments/PaymentGatewayMockTest.php
        print_success "Payment gateway mock tests completed"
        ;;
    verbose)
        print_header "Running All Tests (Verbose)"
        php artisan test --verbose
        print_success "All tests completed"
        ;;
    stop-on-fail)
        print_header "Running Tests (Stop on First Failure)"
        php artisan test --stop-on-failure
        print_success "Tests completed"
        ;;
    parallel)
        print_header "Running Tests in Parallel"
        php artisan test --parallel
        print_success "Parallel tests completed"
        ;;
    help)
        print_header "SATAAB Hotel Telegram Bot - Test Suite"
        echo ""
        echo "Usage: ./RUN_TESTS.sh [command]"
        echo ""
        echo "Commands:"
        echo "  all              Run all tests"
        echo "  feature          Run feature tests only"
        echo "  unit             Run unit tests only"
        echo "  coverage         Run tests with coverage report"
        echo "  coverage-html    Generate HTML coverage report"
        echo "  webhook          Run webhook tests"
        echo "  commands         Run command response tests"
        echo "  flows            Run conversation flow tests"
        echo "  payments         Run payment integration tests"
        echo "  notifications    Run notification tests"
        echo "  integration      Run integration tests"
        echo "  language         Run language service tests"
        echo "  admin            Run admin command tests"
        echo "  api-mock         Run Telegram API mock tests"
        echo "  payment-mock     Run payment gateway mock tests"
        echo "  verbose          Run all tests with verbose output"
        echo "  stop-on-fail     Run tests and stop on first failure"
        echo "  parallel         Run tests in parallel"
        echo "  help             Show this help message"
        echo ""
        ;;
    *)
        echo "Unknown command: $1"
        echo "Run './RUN_TESTS.sh help' for usage information"
        exit 1
        ;;
esac
