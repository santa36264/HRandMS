#!/bin/bash

# SATAAB Hotel Telegram Bot - Production Deployment Script
# Usage: ./scripts/deploy.sh [environment]

set -e

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Configuration
APP_DIR="/var/www/sataab-hotel/backend"
ENVIRONMENT="${1:-production}"
BACKUP_DIR="/backups/sataab-hotel"
LOG_FILE="/var/log/sataab-bot-deploy.log"

# Functions
print_header() {
    echo -e "${BLUE}========================================${NC}"
    echo -e "${BLUE}$1${NC}"
    echo -e "${BLUE}========================================${NC}"
}

print_success() {
    echo -e "${GREEN}✓ $1${NC}"
}

print_error() {
    echo -e "${RED}✗ $1${NC}"
}

print_warning() {
    echo -e "${YELLOW}⚠ $1${NC}"
}

log_message() {
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] $1" >> $LOG_FILE
}

# Check if running as root
if [ "$EUID" -ne 0 ]; then
    print_error "This script must be run as root"
    exit 1
fi

# Start deployment
print_header "SATAAB Hotel Bot - Deployment Script"
echo "Environment: $ENVIRONMENT"
echo "App Directory: $APP_DIR"
echo ""

log_message "Deployment started for $ENVIRONMENT environment"

# Step 1: Create backup
print_header "Step 1: Creating Backup"
mkdir -p $BACKUP_DIR
BACKUP_DATE=$(date +%Y%m%d_%H%M%S)

# Backup database
print_warning "Backing up database..."
mysqldump -u sataab_user -p'password' sataab_hotel | \
    gzip > $BACKUP_DIR/database_$BACKUP_DATE.sql.gz
print_success "Database backed up"

# Backup application
print_warning "Backing up application..."
tar -czf $BACKUP_DIR/app_$BACKUP_DATE.tar.gz \
    $APP_DIR \
    --exclude=node_modules \
    --exclude=vendor \
    --exclude=.git \
    --exclude=storage/logs \
    --exclude=storage/cache
print_success "Application backed up"

log_message "Backup created: $BACKUP_DATE"

# Step 2: Pull latest code
print_header "Step 2: Pulling Latest Code"
cd $APP_DIR
print_warning "Pulling from repository..."
git pull origin main
print_success "Code pulled successfully"

log_message "Code pulled from repository"

# Step 3: Install dependencies
print_header "Step 3: Installing Dependencies"
print_warning "Installing composer dependencies..."
composer install --no-dev --optimize-autoloader
print_success "Dependencies installed"

log_message "Dependencies installed"

# Step 4: Run migrations
print_header "Step 4: Running Database Migrations"
print_warning "Running migrations..."
php artisan migrate --force
print_success "Migrations completed"

log_message "Database migrations completed"

# Step 5: Clear caches
print_header "Step 5: Clearing Caches"
print_warning "Clearing application caches..."
php artisan cache:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
print_success "Caches cleared and rebuilt"

log_message "Caches cleared and rebuilt"

# Step 6: Restart queue workers
print_header "Step 6: Restarting Queue Workers"
print_warning "Restarting workers..."
supervisorctl restart sataab-hotel-worker:*
supervisorctl restart sataab-hotel-scheduler
print_success "Workers restarted"

log_message "Queue workers restarted"

# Step 7: Verify deployment
print_header "Step 7: Verifying Deployment"

# Check PHP-FPM
if systemctl is-active --quiet php8.1-fpm; then
    print_success "PHP-FPM is running"
else
    print_error "PHP-FPM is not running"
    log_message "ERROR: PHP-FPM is not running"
    exit 1
fi

# Check Nginx
if systemctl is-active --quiet nginx; then
    print_success "Nginx is running"
else
    print_error "Nginx is not running"
    log_message "ERROR: Nginx is not running"
    exit 1
fi

# Check MySQL
if systemctl is-active --quiet mysql; then
    print_success "MySQL is running"
else
    print_error "MySQL is not running"
    log_message "ERROR: MySQL is not running"
    exit 1
fi

# Check Redis
if systemctl is-active --quiet redis-server; then
    print_success "Redis is running"
else
    print_error "Redis is not running"
    log_message "ERROR: Redis is not running"
    exit 1
fi

# Check Supervisor
WORKER_COUNT=$(supervisorctl status sataab-hotel-worker:* | grep -c "RUNNING")
if [ "$WORKER_COUNT" -eq 4 ]; then
    print_success "All 4 queue workers are running"
else
    print_warning "Only $WORKER_COUNT workers running (expected 4)"
    log_message "WARNING: Only $WORKER_COUNT workers running"
fi

# Step 8: Test webhook
print_header "Step 8: Testing Webhook"
WEBHOOK_STATUS=$(php artisan telegram:webhook-info --token=$TELEGRAM_BOT_TOKEN 2>/dev/null | grep -i "url" || echo "")
if [ ! -z "$WEBHOOK_STATUS" ]; then
    print_success "Webhook is configured"
else
    print_warning "Could not verify webhook status"
fi

log_message "Webhook verification completed"

# Step 9: Cleanup old backups
print_header "Step 9: Cleaning Up Old Backups"
RETENTION_DAYS=30
find $BACKUP_DIR -type f -mtime +$RETENTION_DAYS -delete
print_success "Old backups cleaned up (retention: $RETENTION_DAYS days)"

log_message "Old backups cleaned up"

# Deployment complete
print_header "Deployment Complete!"
echo ""
echo "Summary:"
echo "  Environment: $ENVIRONMENT"
echo "  Backup Date: $BACKUP_DATE"
echo "  Backup Location: $BACKUP_DIR"
echo "  Log File: $LOG_FILE"
echo ""
echo "Next steps:"
echo "  1. Monitor logs: tail -f $APP_DIR/storage/logs/laravel.log"
echo "  2. Check workers: supervisorctl status"
echo "  3. Test bot: Send /start command to bot"
echo ""

log_message "Deployment completed successfully"

# Send notification (optional)
if [ ! -z "$SLACK_WEBHOOK" ]; then
    curl -X POST $SLACK_WEBHOOK \
        -H 'Content-Type: application/json' \
        -d "{\"text\": \"✓ SATAAB Bot deployment completed successfully ($ENVIRONMENT)\"}"
fi

exit 0
