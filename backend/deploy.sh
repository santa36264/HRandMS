#!/usr/bin/env bash
# =============================================================================
# HR&MS — Laravel Production Deploy Script
# =============================================================================
# Usage:
#   chmod +x deploy.sh
#   ./deploy.sh              # full deploy
#   ./deploy.sh --skip-migrate   # skip migrations (hotfix deploys)
#   ./deploy.sh --fresh-seed     # re-seed (staging only — DESTROYS DATA)
#
# Assumptions:
#   - Running as the web server user (e.g. www-data) or a deploy user
#   - .env is already in place with correct production values
#   - PHP 8.1+, Composer, and the queue worker (Supervisor) are installed
# =============================================================================

set -euo pipefail

# ── Colour helpers ────────────────────────────────────────────────────────────
GREEN='\033[0;32m'; YELLOW='\033[1;33m'; RED='\033[0;31m'; NC='\033[0m'
info()    { echo -e "${GREEN}[deploy]${NC} $*"; }
warning() { echo -e "${YELLOW}[deploy]${NC} $*"; }
error()   { echo -e "${RED}[deploy]${NC} $*" >&2; exit 1; }

# ── Argument parsing ──────────────────────────────────────────────────────────
SKIP_MIGRATE=false
FRESH_SEED=false

for arg in "$@"; do
  case $arg in
    --skip-migrate) SKIP_MIGRATE=true ;;
    --fresh-seed)   FRESH_SEED=true   ;;
    *) error "Unknown argument: $arg" ;;
  esac
done

# ── Pre-flight checks ─────────────────────────────────────────────────────────
[[ -f ".env" ]]          || error ".env file not found. Copy .env.example and fill in values."
[[ -f "artisan" ]]       || error "Run this script from the Laravel root directory."
command -v php      &>/dev/null || error "PHP not found in PATH."
command -v composer &>/dev/null || error "Composer not found in PATH."

info "Starting deployment..."

# ── 1. Maintenance mode ───────────────────────────────────────────────────────
info "Enabling maintenance mode..."
php artisan down --retry=30 --refresh=15

# Ensure maintenance mode is lifted even if the script fails
trap 'info "Lifting maintenance mode after error..."; php artisan up' ERR

# ── 2. Pull latest code ───────────────────────────────────────────────────────
info "Pulling latest code from git..."
git pull origin main --ff-only

# ── 3. PHP dependencies ───────────────────────────────────────────────────────
info "Installing Composer dependencies (no-dev, optimised)..."
composer install \
  --no-dev \
  --no-interaction \
  --prefer-dist \
  --optimize-autoloader \
  --no-progress

# ── 4. Migrations ─────────────────────────────────────────────────────────────
if [[ "$SKIP_MIGRATE" == false ]]; then
  info "Running database migrations..."
  php artisan migrate --force

  if [[ "$FRESH_SEED" == true ]]; then
    warning "Running fresh seed — ALL DATA WILL BE LOST."
    php artisan db:seed --force
  fi
else
  warning "Skipping migrations (--skip-migrate flag set)."
fi

# ── 5. Clear & rebuild caches ─────────────────────────────────────────────────
info "Clearing stale caches..."
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan event:clear

info "Rebuilding optimised caches..."
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

# ── 6. Storage link ───────────────────────────────────────────────────────────
info "Ensuring storage symlink exists..."
php artisan storage:link --force 2>/dev/null || true

# ── 7. File permissions ───────────────────────────────────────────────────────
info "Setting file permissions..."
chmod -R 775 storage bootstrap/cache
# Uncomment and adjust if running under a specific user:
# chown -R www-data:www-data storage bootstrap/cache

# ── 8. Queue workers ──────────────────────────────────────────────────────────
info "Restarting queue workers..."
php artisan queue:restart
# Workers are managed by Supervisor — it will respawn them automatically.
# To check worker status: sudo supervisorctl status

# ── 9. Prune old failed jobs (keep last 7 days) ───────────────────────────────
info "Pruning failed jobs older than 7 days..."
php artisan queue:prune-failed --hours=168

# ── 10. Lift maintenance mode ─────────────────────────────────────────────────
trap - ERR
info "Lifting maintenance mode..."
php artisan up

info "Deployment complete."
