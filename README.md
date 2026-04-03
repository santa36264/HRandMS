# Hotel Reservation & Management System (HR&MS)

A full-stack hotel management platform built with **Laravel 10** (API backend) and **Vue 3 + Vite** (SPA frontend). Supports room browsing, booking, Ethiopian payment gateways (Telebirr & CBE Birr), QR check-in, email notifications, and an admin analytics dashboard.

---

## Tech Stack

| Layer | Technology |
|-------|-----------|
| Backend | PHP 8.1, Laravel 10, Laravel Sanctum |
| Frontend | Vue 3, Vite 8, Pinia, Vue Router 4 |
| Database | MySQL 8 |
| Queue | Laravel Queue (database driver) |
| Charts | Chart.js 4 |
| Payments | chapa |
| Testing | PHPUnit 10 |

| Process mgr | Supervisor |

---

## Features

**Guest**
- Browse and filter rooms by type, capacity, price, floor, and amenities
- Real-time availability search with date range picker
- 3-step booking wizard with live price breakdown (ETB)
- Telebirr and CBE Birr payment integration
- QR code check-in pass (HMAC-signed, 24h expiry, downloadable)
- Booking history with cancellation and status tracking
- Star-rating review system (post-checkout)
- Payment receipt history

**Admin**
- Full room CRUD (create, edit, status management)
- Bookings table with filters, sort, and inline status updates
- Payment management with manual verify and refund
- Analytics dashboard — revenue, occupancy, per-room performance, payment breakdown
- All data visualised with Chart.js bar, line, doughnut, and pie charts

**Auth**
- Register / login with Laravel Sanctum token auth
- OTP-based email verification (6-digit, 2-min expiry, auto-focus input)
- Forgot / reset password flow
- Profile update and password change
- Logout single device or all devices

**Notifications (queued)**
- Booking confirmed email
- Payment receipt email
- Check-in reminder (24h before arrival)
- Review request (24h after checkout)
- All emails use responsive HTML templates with Outlook-safe VML buttons

**Infrastructure**
- Global Axios interceptors for 401/403/500/503/network errors
- Toast notification system (4 types, auto-dismiss)
- Inline field-level validation errors (422)
- Vue `ApiErrorBoundary` component for section-level error isolation
- Laravel queue jobs with retry, backoff, and `deleteWhenMissingModels`
- Artisan commands with `--dry-run` and `--date` flags
- PHPUnit test suite — 54 tests covering availability, booking, and HTTP stack
- OpenAPI 3.1 spec (`backend/openapi.yaml`)
- Production deploy script with maintenance mode, cache rebuild, and Supervisor restart

---

## Project Structure

```
hrms/
├── backend/          # Laravel 10 API
│   ├── app/
│   │   ├── Http/Controllers/API/
│   │   │   ├── Admin/        # Room, Booking, Payment, Analytics
│   │   │   ├── Auth/         # AuthController, EmailVerificationController
│   │   │   └── Guest/        # Room, Booking, Payment, Review
│   │   ├── Services/         # Business logic layer
│   │   ├── Payments/         # Gateway abstraction (Telebirr, CBE Birr)
│   │   ├── Jobs/             # Queued email jobs
│   │   ├── Notifications/    # Mailable notification classes
│   │   └── Models/           # Eloquent models
│   ├── database/
│   │   ├── migrations/
│   │   ├── seeders/
│   │   └── factories/
│   ├── tests/
│   │   ├── Unit/             # AvailabilityServiceTest, BookingServiceTest
│   │   └── Feature/          # BookingAvailabilityTest (HTTP stack)
│   ├── supervisor/           # hrms-worker.conf
│   ├── deploy.sh
│   └── openapi.yaml
│
├── frontend/         # Vue 3 + Vite SPA
│   ├── src/
│   │   ├── components/
│   │   │   ├── admin/        # BookingsTable, BookingStatusModal
│   │   │   ├── booking/      # CheckInQrCode
│   │   │   ├── charts/       # Bar, Line, Doughnut, Pie wrappers
│   │   │   ├── reviews/      # StarRating, ReviewForm
│   │   │   ├── rooms/        # RoomCard, BookingForm
│   │   │   └── ui/           # ToastContainer, ErrorBanner, ErrorState,
│   │   │                     # ApiErrorBoundary, FieldError
│   │   ├── composables/      # useApiError, useToast
│   │   ├── layouts/          # AdminLayout, GuestLayout, AuthLayout
│   │   ├── plugins/          # axios.js (interceptors)
│   │   ├── router/           # index.js (guards)
│   │   ├── services/         # auth.js, bookings.js
│   │   ├── stores/           # auth.js, toast.js (Pinia)
│   │   └── views/
│   │       ├── admin/        # AdminDashboard, BookingsView
│   │       ├── auth/         # Login, Register, VerifyEmail, ...
│   │       ├── rooms/        # BookingConfirmView
│   │       └── ProfileView
│   └── vite.config.js
│
├── nginx/            # hrms.conf (frontend + API virtual hosts)
├── DEPLOYMENT.md
└── README.md
```

---

## Prerequisites

- PHP 8.1+
- Composer 2.x
- Node.js 18 LTS
- MySQL 8.0
- (Production) Nginx, Supervisor

---

## Local Development Setup

### 1. Clone

```bash
git clone https://github.com/your-org/hrms.git
cd hrms
```

### 2. Backend

```bash
cd backend

# Install dependencies
composer install

# Environment
cp .env.example .env
php artisan key:generate

# Edit .env — set DB_DATABASE, DB_USERNAME, DB_PASSWORD, MAIL_*, payment keys
```

Create the database, then run migrations and seed:

```bash
php artisan migrate --seed
```

Generate the storage symlink:

```bash
php artisan storage:link
```

Start the development server:

```bash
php artisan serve
# Listening on http://localhost:8000
```

Start the queue worker (separate terminal):

```bash
php artisan queue:work --queue=high,default,low
```

### 3. Frontend

```bash
cd frontend

npm install

# Environment
cp .env.example .env
# VITE_API_URL is already set to http://localhost:8000/api
```

Start the dev server:

```bash
npm run dev
# Listening on http://localhost:5173
```

The Vite dev proxy forwards `/api` requests to Laravel automatically — no CORS issues during development.

---

## Running Tests

```bash
cd backend
php artisan test
```

Or with coverage:

```bash
php artisan test --coverage
```

The test suite uses an in-memory SQLite database — no separate test DB needed.

---

## Building for Production

```bash
cd frontend
npm run build:production   # outputs to frontend/dist/
```

For staging (includes source maps):

```bash
npm run build:staging
```

See `DEPLOYMENT.md` for the full server setup, Nginx config, Supervisor, cron, and deploy script usage.

---

## Environment Variables

| File | Purpose |
|------|---------|
| `backend/.env.example` | All Laravel config variables, fully documented |
| `frontend/.env.example` | All Vite variables (`VITE_` prefix required) |
| `frontend/.env.staging` | Staging overrides |
| `frontend/.env.production` | Production overrides |

Key variables to set before first run:

```dotenv
# backend/.env
APP_KEY=                   # php artisan key:generate
DB_DATABASE=hrms_db
DB_USERNAME=root
DB_PASSWORD=
MAIL_HOST=smtp.mailtrap.io
MAIL_USERNAME=
MAIL_PASSWORD=
CHECKIN_QR_SECRET=         # random 32-byte hex string
TELEBIRR_APP_ID=
TELEBIRR_APP_KEY=
CBE_BIRR_MERCHANT_ID=
CBE_BIRR_API_KEY=
```

```dotenv
# frontend/.env
VITE_API_URL=http://localhost:8000/api
```

---

## API Documentation

The full OpenAPI 3.1 spec is at `backend/openapi.yaml`. You can explore it with:

```bash
# Using Swagger UI via npx (no install needed)
npx @redocly/cli preview-docs backend/openapi.yaml
```

Or import the file directly into [Postman](https://www.postman.com) or [Insomnia](https://insomnia.rest).

---

## Artisan Commands

```bash
# Send check-in reminder emails (runs daily via scheduler)
php artisan reminders:checkin

# Dry run — shows who would be emailed without sending
php artisan reminders:checkin --dry-run

# Send review request emails
php artisan reminders:reviews

# Target a specific date
php artisan reminders:checkin --date=2026-04-10
```

---

## License

MIT
"# HRandMS" 
