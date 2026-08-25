# Customer Achievement Assessment API

A Laravel 13 / PHP 8.3 API that gamifies a simple purchase flow: users buy products, unlock **achievements** based on purchase milestones, unlock **badges** based on how many achievements they've earned, and get paid a cash reward via **Paystack** when a badge unlocks.

## Design choices

- **Repository pattern** — every model has a `Contracts\Repositories\*Interface` bound to a `Repositories\*` implementation in `AppServiceProvider`, keeping controllers/actions decoupled from Eloquent.
- **Actions** — single-purpose classes in `app/Actions` (`RecordPurchaseAction`, `UnlockAchievementAction`, `UnlockBadgeAction`, `PayoutAction`, `BankAccountAction`) encapsulate each unit of business logic. `UnlockAchievementAction`/`UnlockBadgeAction` each pair a "check every eligible one for this user" method with a "unlock this one" method; `PayoutAction` pairs "attempt" (send via the gateway) with "verify" (reconcile against Paystack).
- **Events & queued listeners** — achievement/badge unlocking and the Paystack payout run as `ShouldQueue` listeners off the request cycle, processed by a dedicated `worker` container/process.
- **Race-safety** — unlock actions and the payout listener wrap their check-then-act logic in `Cache::lock()`, backed by unique DB constraints on `(user_id, achievement_id)` and `(user_id, badge_id)` as a second line of defense against concurrent queue workers double-unlocking. Payouts get the same protection a different way: `firstOrCreatePending` is keyed on `(user_id, reason)`, so a retried/redelivered queue job reuses the existing payout instead of creating (and paying) a second one.
- **Payout lifecycle** — a badge unlock creates a `Payout` (`Pending` → `Paid`/`Failed`). It's reconciled two ways: a Paystack webhook (`POST /api/paystack/webhook`, authenticated via HMAC-SHA512 signature verification instead of a JWT) updates it as soon as Paystack calls back, and the `php artisan payouts:verify` console command polls Paystack directly for any payout still `Pending` — useful as a fallback if a webhook delivery is missed. Linking a bank account after a payout was stuck pending (no `paystack_recipient_code` yet) automatically retries it via `BankAccountAction`.
- **Payment gateway abstraction** — `PaymentGatewayInterface` / `PaystackService` isolates the Paystack HTTP calls so the gateway can be swapped or mocked in tests.
- **API Resources** — `app/Http/Resources` (`ProductResource`, `PurchaseResource`, `AchievementResource`, `UserAchievementResource`) shape every JSON response by hand, so internal IDs and foreign keys are never accidentally exposed.
- **UUID route keys** — `User`, `Product`, and `Purchase` use a `HasUuid` trait so they're referenced by UUID (not incrementing ID, so as not to expose primary keys) in URLs and API responses.
- **JWT auth** — `tymon/jwt-auth` provides the `api` guard; no session-based auth.

## Tech stack

PHP 8.3 · Laravel 13 · MySQL · database-backed queues · `tymon/jwt-auth` · Pest (on PHPUnit) · Docker (nginx + php-fpm).

## Setup

### Option A — Docker

```bash
cp .env.example .env
docker compose up --build
```

- `api` — nginx + php-fpm, served at http://localhost:8000
- `worker` — runs `php artisan queue:work`

`docker-compose.yml` bind-mounts the project root over `/var/www/html`, which hides whatever `vendor/` the image built during `docker build` — so the `api` container's entrypoint runs `composer install` itself on every start (using the `composer` binary baked into the image), generates `APP_KEY`/`JWT_SECRET` if `.env` doesn't have them yet, then runs `php artisan migrate --seed --force`. All of that writes back to the bind-mounted host directory just like any other change made from inside the container, so **the host never needs its own PHP, Composer, or manual `key:generate`/`jwt:secret` calls** — the very first `docker compose up --build` sets everything up for you. `worker` waits for `api` to finish this before starting `queue:work`, rather than racing it to do the same setup.

> If you'd rather have `vendor/` (and your IDE's autocomplete) ready *before* the container finishes booting, you can still run `composer install` on the host yourself beforehand — it's optional now, not required.

> `.env` isn't passed into the containers as env vars: Laravel just reads the bind-mounted file directly, same as running locally. This was done to make it easier to run tests on the container.

> The compose file doesn't ship a database container. Point `DB_HOST` in `.env` at your own MySQL instance (e.g. `host.docker.internal` for one running on your host machine) — the database is expected to already exist and be referenced in `.env`.

### Option B — Local

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan jwt:secret
php artisan migrate --seed
php artisan serve
```

Set `PAYSTACK_SECRET_KEY` / `PAYSTACK_BASE_URL` in `.env` if you want badge payouts to actually call Paystack.

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

```bash
composer test
# or
php artisan test

# or, inside the running api container:
docker compose exec api php artisan test
```

Tests run against an in-memory SQLite DB with `QUEUE_CONNECTION=sync`, so queued listeners execute inline — no worker needed while testing. Feature tests (`tests/Feature`) boot the app with `RefreshDatabase`; unit tests (`tests/Unit`) exercise actions/listeners/services in isolation without touching the DB.
