# Customer Achievement Assessment API

A Laravel 13 / PHP 8.3 API that gamifies a simple purchase flow: users buy products, unlock **achievements** based on purchase milestones, unlock **badges** based on how many achievements they've earned, and get paid a cash reward via **Paystack** when a badge unlocks.

## Design choices

- **Repository pattern** — every model has a `Contracts\Repositories\*Interface` bound to a `Repositories\*` implementation in `AppServiceProvider`, so controllers and actions never touch Eloquent directly.
- **Actions** — business logic stays out of controllers; each unit of it lives in its own single-purpose class under `app/Actions` (`RecordPurchaseAction`, `UnlockAchievementAction`, `UnlockBadgeAction`, `PayoutAction`, `BankAccountAction`). `UnlockAchievementAction`/`UnlockBadgeAction` each pair a "check every eligible one for this user" method with a "unlock this one" method, and `PayoutAction` pairs "attempt" (send via the gateway) with "verify" (reconcile against Paystack) — these were merged once it became clear how similar the original classes were to each other.
- **Events & queued listeners** — to avoid a slow Paystack call holding up a purchase response, achievement/badge unlocking and the payout itself run as `ShouldQueue` listeners off the request cycle, handled by a dedicated `worker` container/process.
- **Race-safety** — since the unlock actions and the payout listener all do a check-then-act, that logic is wrapped in `Cache::lock()` and backed by unique DB constraints on `(user_id, achievement_id)` and `(user_id, badge_id)`, in case two queue workers ever pick up the same job. Payouts are handled a little differently: `firstOrCreatePending` is keyed on `(user_id, reason)`, so a retried/redelivered queue job just finds the existing payout instead of creating (and paying) a second one.
- **Payout lifecycle** — a badge unlock creates a `Payout` (`Pending` → `Paid`/`Failed`). Rather than relying on a single reconciliation path, two are wired up: a Paystack webhook (`POST /api/paystack/webhook`, authenticated via HMAC-SHA512 signature verification since Paystack has no JWT of its own to send) updates it as soon as Paystack calls back, and a `php artisan payouts:verify` command polls Paystack directly for anything still `Pending`, as a fallback in case a webhook delivery gets missed. Linking a bank account after a payout got stuck pending (no `paystack_recipient_code` yet) automatically retries it, via `BankAccountAction`.
- **Payment gateway abstraction** — `PaymentGatewayInterface` / `PaystackService` sit between the app and Paystack's HTTP API, so the gateway can be swapped or mocked out in tests without touching anything else.
- **API Resources** — every response gets a hand-written Resource class (`ProductResource`, `PurchaseResource`, `AchievementResource`, `UserAchievementResource`) instead of returning raw models, so an internal ID or foreign key never accidentally leaks into the JSON.
- **Audit log** — every Action records what it did to an `audit_logs` table (`AuditLogRepositoryInterface`) as it happens — purchases, achievement/badge unlocks, bank account links, payout attempts/verifications — kept for internal record-keeping.
- **UUID route keys** — `User`, `Product`, and `Purchase` share a `HasUuid` trait so they're referenced by UUID rather than their incrementing ID, keeping primary keys out of URLs and API responses.
- **JWT auth** — `tymon/jwt-auth` provides the `api` guard instead of session-based auth, since this is a pure API with no browser session to lean on.

## Tech stack

PHP 8.3 · Laravel 13 · MySQL · database-backed queues · `tymon/jwt-auth` · Pest (on PHPUnit) · Docker (nginx + php-fpm).

## Setup

```bash
git clone https://github.com/izunnamiles/achievement-assessment.git
cd achievement-assessment
```

### Database

Neither setup option below ships or creates a database server — you need a MySQL instance reachable from the app, with the database itself already created. Copy `.env.example` to `.env` and point the `DB_*` variables at it:

```bash
cp .env.example .env
```

```dotenv
DB_CONNECTION=mysql
DB_HOST=127.0.0.1        # or host.docker.internal (see Option A), or a container/service name
DB_PORT=3306
DB_DATABASE=laravel      # must already exist - migrations create tables, not the database
DB_USERNAME=laravel
DB_PASSWORD=secret
```

Migrations (run automatically below) create the schema; they don't create the database itself.

### Option A — Docker

```bash
docker compose up --build
```

- `api` — nginx + php-fpm, served at http://localhost:8000
- `worker` — runs `php artisan queue:work`

`docker-compose.yml` bind-mounts the project root over `/var/www/html`, which hides whatever `vendor/` the image built during `docker build` — so the `api` container's entrypoint runs `composer install` itself on every start (using the `composer` binary baked into the image), generates `APP_KEY`/`JWT_SECRET` if `.env` doesn't have them yet, then runs `php artisan migrate --seed --force`. All of that writes back to the bind-mounted host directory just like any other change made from inside the container, so **the host never needs its own PHP, Composer, or manual `key:generate`/`jwt:secret` calls** — the very first `docker compose up --build` sets everything up for you. `worker` waits for `api` to finish this before starting `queue:work`, rather than racing it to do the same setup.

> If you'd rather have `vendor/` (and your IDE's autocomplete) ready *before* the container finishes booting, you can still run `composer install` on the host yourself beforehand — it's optional now, not required.

> `.env` isn't passed into the containers as env vars: Laravel just reads the bind-mounted file directly, same as running locally. This was done to make it easier to run tests on the container.

> The compose file doesn't ship a database container. Point `DB_HOST` in `.env` at your own MySQL instance (e.g. `host.docker.internal` for one running on your host machine) — see [Database](#database) above for the rest of the `DB_*` values.

### Option B — Local

```bash
composer install
php artisan key:generate
php artisan jwt:secret
php artisan migrate --seed
php artisan serve
```

`QUEUE_CONNECTION=database` by default, so achievement/badge unlocking and payouts are queued, not processed inline — run a worker in a separate terminal or nothing past the purchase itself will happen:

```bash
php artisan queue:work
```

Set `PAYSTACK_SECRET_KEY` / `PAYSTACK_BASE_URL` in `.env` for integration with the Paystack transfer API for payouts.

A seeded test user is available: `test@example.com` / `password@123`.

## API

| Method | Endpoint | Auth | Description |
|---|---|---|---|
| POST | `/api/auth/login` | – | Log in, returns JWT |
| POST | `/api/auth/logout` | ✓ | Invalidate token |
| GET | `/api/products` | ✓ | List products |
| GET | `/api/products/{product}` | ✓ | Show a product |
| POST | `/api/purchases` | ✓ | Buy a product (`product_id`, `quantity`) |
| GET | `/api/achievements` | ✓ | List the user's unlocked achievements |
| POST | `/api/bank-account` | ✓ | Link a payout bank account (retries any pending payout) |
| POST | `/api/paystack/webhook` | – | Paystack payout status callback (HMAC-signed, not JWT-authenticated) |
| GET | `/users/{user}/achievements` | – | Web view of a user's achievement progress |

Run `php artisan payouts:verify` (manually or on a cron) to poll Paystack for any payout still `Pending`, as a fallback for a missed webhook delivery.

## Testing

To run tests, use the following:

```bash
composer test
# or
php artisan test

# or, inside the running api container:
docker compose exec api php artisan test
```

Tests run against an in-memory SQLite DB with `QUEUE_CONNECTION=sync`, so queued listeners execute inline — no worker needed while testing.
