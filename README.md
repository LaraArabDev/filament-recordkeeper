<p align="center">
  <img src="art/banner.png" alt="Filament Recordkeeper" width="100%">
</p>

<p align="center">
  <strong>Production-grade audit trail for Laravel — powered by PHP 8 attributes, built for Filament.</strong>
</p>

<p align="center">
  <a href="https://github.com/LaraArabDev/filament-recordkeeper/actions/workflows/tests.yml">
    <img src="https://github.com/LaraArabDev/filament-recordkeeper/actions/workflows/tests.yml/badge.svg" alt="Tests">
  </a>
  <a href="https://github.com/LaraArabDev/filament-recordkeeper/actions/workflows/static-analysis.yml">
    <img src="https://github.com/LaraArabDev/filament-recordkeeper/actions/workflows/static-analysis.yml/badge.svg" alt="Static Analysis">
  </a>
  <a href="https://github.com/LaraArabDev/filament-recordkeeper/actions/workflows/code-style.yml">
    <img src="https://github.com/LaraArabDev/filament-recordkeeper/actions/workflows/code-style.yml/badge.svg" alt="Code Style">
  </a>
  <a href="https://codecov.io/gh/LaraArabDev/filament-recordkeeper">
    <img src="https://codecov.io/gh/LaraArabDev/filament-recordkeeper/graph/badge.svg" alt="Coverage">
  </a>
  <a href="https://github.com/LaraArabDev/filament-recordkeeper/actions/workflows/benchmarks.yml">
    <img src="https://img.shields.io/badge/benchmark-phpbench-blue" alt="PHPBench">
  </a>
  <a href="LICENSE">
    <img src="https://img.shields.io/badge/License-MIT-yellow.svg" alt="License: MIT">
  </a>
</p>

---

## What is Filament Recordkeeper?

**Filament Recordkeeper** is a Laravel package that sits on top of [`owen-it/laravel-auditing`](https://laravel-auditing.com/) and turns it into a full observability platform for your application. It doesn't reinvent auditing — it supercharges it.

Out of the box, laravel-auditing tracks model changes. Recordkeeper extends that to cover **every meaningful action** in your app:

| What happened | How Recordkeeper tracks it |
|---|---|
| A model was created / updated / deleted | Eloquent audit (via laravel-auditing) |
| A user hit a route or API endpoint | Route middleware writes an `Audit` row |
| A queued job ran | Job listener records start, result, and duration |
| An Artisan command executed | Command listener records exit code, duration, memory, and audit impact |
| A job made an outbound HTTP call | HTTP tracker links the request to the job audit |
| An admin reverted a change | Rollback is recorded as its own audit event |

Everything is stored in a single `audits` table with a clean schema. You can query it with a fluent builder, revert changes from the CLI or Filament UI, prune old records on a schedule, and protect sensitive fields with redaction or field-level encryption — all configured with PHP 8 attributes, no YAML, no callbacks.

**Filament 5 is optional.** The core works headless. The Filament plugin adds a full audit resource with smart filters, before/after diff viewer, timeline, stats widget, and a permission-gated revert button.

---

## Features

- **PHP 8 attributes** — configure auditing declaratively with `#[Auditable]`, `#[Redact]`, `#[Encrypt]`, `#[AuditExclude]`
- **Route & API auditing** — middleware logs every HTTP request with guard, actor, duration, and status
- **Job auditing** — queued and sync jobs recorded with pass/fail and duration
- **Command auditing** — Artisan commands tracked with memory peak, audit impact count, and anomaly detection
- **Outbound HTTP tracking** — HTTP calls made inside a job linked to the parent job audit
- **Rollback** — revert any model audit, restore soft-deleted records, or roll back an entire batch atomically
- **Batch auditing** — group related writes under a shared `batch_id` for bulk rollback
- **Fluent query builder** — filter audits by model, actor, guard, tag, batch, date range, and more
- **Sensitive-data protection** — pattern-based auto-redaction and field-level AES encryption at write time
- **Date-based pruning** — `MassPrunable` retention policy, configurable per model
- **Manual log** — write arbitrary audit events from anywhere in your code
- **Filament 5 resource** — diff viewer, timeline, stats widget, revert button

---

## Requirements

| | Version |
|---|---|
| PHP | 8.2 / 8.3 / 8.4 |
| Laravel | 11 / 12 |
| owen-it/laravel-auditing | ^13.0 |
| Filament | ^5.0 *(optional)* |

---

## Installation

```bash
composer require laraarabdev/filament-recordkeeper
php artisan recordkeeper:install
php artisan migrate
```

The install wizard publishes config and migrations, and skips any files already present. Force-overwrite with:

```bash
php artisan recordkeeper:install --force
```

### With Filament

Register the plugin in your panel provider:

```php
use LaraArabDev\Recordkeeper\Filament\RecordkeeperPlugin;

public function panel(Panel $panel): Panel
{
    return $panel->plugins([
        RecordkeeperPlugin::make()
            ->enableRollback()
            ->enableTimeline()
            ->enableStatsWidget()
            ->navigationGroup('Audit'),
    ]);
}
```

### Without Filament (headless / API)

Add the trait and attributes to your models:

```php
use LaraArabDev\Recordkeeper\Attributes\Auditable;
use LaraArabDev\Recordkeeper\Attributes\Redact;
use LaraArabDev\Recordkeeper\Concerns\AuditsChanges;

#[Auditable(events: ['created', 'updated', 'deleted'], retentionDays: 365)]
#[Redact('discount_code')]
class Order extends Model implements \OwenIt\Auditing\Contracts\Auditable
{
    use AuditsChanges;
}
```

---

## Usage

### Model Attributes

| Attribute | What it does |
|---|---|
| `#[Auditable]` | Enable auditing; accepts `events`, `retentionDays`, `threshold`, `tags` |
| `#[AuditExclude('field')]` | Exclude fields from audit records |
| `#[Redact('field')]` | Replace field value with `***` |
| `#[Encrypt('field')]` | Encrypt field in audit (decrypted automatically on rollback) |
| `#[Audit('event')]` | Fire a custom named audit event |

```php
#[Auditable(events: ['created', 'updated', 'deleted'], tags: ['payments'])]
#[AuditExclude('internal_notes')]
#[Redact('cvv')]
#[Encrypt('national_id')]
class Payment extends Model implements \OwenIt\Auditing\Contracts\Auditable
{
    use AuditsChanges;
}
```

---

### Route Auditing

```php
// Web route — stores guard = 'web', resolves auth()->user()
Route::middleware('audit')->post('/pay', PayController::class);

// API route — stores guard = 'api', resolves actor from token
Route::middleware(['auth:sanctum', 'audit.api'])->apiResource('orders', OrderApiController::class);
```

Each request creates an `Audit` row with the route name, HTTP method, response status, duration in ms, and the authenticated actor.

---

### Job & Command Auditing

Enable in config:

```php
// config/recordkeeper.php
'jobs'     => ['enabled' => true],
'commands' => [
    'enabled' => true,
    'metrics' => [
        'memory'      => true,   // record peak memory
        'audit_count' => true,   // count model changes triggered by the command
        'anomaly'     => true,   // flag runs that spike above historical average
    ],
],
```

Every completed job and command gets an audit row. Command audits include:

```json
{
  "command": "app:sync-orders",
  "exit_code": 0,
  "duration_ms": 842,
  "memory_peak_mb": 34.5,
  "audit_count": 217,
  "anomaly": true,
  "anomaly_reason": "audit_count 217 > 2x avg (98)"
}
```

---

### Outbound HTTP Tracking

When a job makes HTTP calls (Stripe, HubSpot, etc.), Recordkeeper links them to the parent job audit:

```php
// config/recordkeeper.php
'http' => [
    'enabled'         => true,
    'capture_headers' => true,
    'capture_body'    => false,
    'exclude_hosts'   => ['internal.example.com'],
    'queue'           => false,  // true = async write
],
```

Each outbound request is stored in `audit_http_requests` and shown as a tab on the Filament audit detail page.

---

### Rollback

```php
// Roll back a single audit
$audit = $order->audits()->rollbackable()->latest('id')->first();
$audit->rollback();

// Dry-run first — returns a preview without writing
$preview = $audit->rollback(dryRun: true);

// Roll back an entire batch atomically
Recordkeeper::rollbackBatch('nightly-import');

// Roll back by audit ID
Recordkeeper::rollback($auditId);
```

Rollback handles encrypted fields, SoftDeletes, and sequential re-rollback automatically.

---

### Batch Auditing

```php
Recordkeeper::batch('nightly-import-2025-01', function () {
    Order::create([...]);
    Order::create([...]);
    // All audits share batch_id = 'nightly-import-2025-01'
});

// Roll back everything in the batch
Recordkeeper::rollbackBatch('nightly-import-2025-01');
```

---

### Fluent Query Builder

```php
use LaraArabDev\Recordkeeper\Support\AuditQuery;

$audits = app(AuditQuery::class)
    ->model('Order')
    ->event(['created', 'updated'])
    ->actor(42, 'Admin')
    ->guard('api')
    ->tag('finance')
    ->since('-7 days')
    ->rollbackable()
    ->latest()
    ->limit(50)
    ->builder()
    ->get();
```

| Method | Description |
|---|---|
| `->model(string)` | Filter by model (short name or FQCN) |
| `->event(string\|array)` | Filter by event name(s) |
| `->actor(id, type?)` | Filter by `user_id` + optional `user_type` |
| `->guard(string)` | Filter by auth guard |
| `->tag(string\|array)` | Filter by tag(s) |
| `->batch(string)` | Filter by `batch_id` |
| `->between(from, until)` | Date range |
| `->since(from)` | Created after date |
| `->rollbackable()` | Only model-change events |
| `->search(string)` | Full-text search across key fields |
| `->latest()` | Order newest first |
| `->builder()` | Return the underlying Eloquent Builder |

---

### Audit Model Scopes

```php
use LaraArabDev\Recordkeeper\Models\Audit;

Audit::forGuard('api')->get();
Audit::forModel('Order')->latest()->get();
Audit::forSubject($order)->get();
Audit::forActor($admin)->get();
Audit::forActor(42, 'Admin')->get();
Audit::forBatch('nightly-import')->get();
Audit::rollbackable()->latest('id')->get();
Audit::routeHits()->where('created_at', '>=', now()->subDay())->get();
```

---

### Manual Audit Log

```php
// System event with no model subject
Recordkeeper::log('payment.gateway.timeout', context: [
    'gateway' => 'stripe',
    'attempt' => 3,
]);

// Event against a specific model
Recordkeeper::log('export.triggered', subject: $order, context: ['format' => 'csv']);
```

---

### Filament — Add Audit History to a Resource

```php
use LaraArabDev\Recordkeeper\Filament\Concerns\HasAuditHistory;

class OrderResource extends Resource
{
    use HasAuditHistory;
}
```

Or use the relation manager directly:

```php
use LaraArabDev\Recordkeeper\Filament\RelationManagers\AuditsRelationManager;

public static function getRelationManagers(): array
{
    return [AuditsRelationManager::class];
}
```

---

## CLI Commands

```bash
# Publish config and migrations
php artisan recordkeeper:install

# Search audits
php artisan recordkeeper:search --model=Order --event=updated --since="-7 days" --json

# Show a single audit with full diff
php artisan recordkeeper:show 1842

# Roll back — dry-run first, then apply
php artisan recordkeeper:rollback 1842 --dry-run
php artisan recordkeeper:rollback 1842 --yes

# Roll back an entire batch
php artisan recordkeeper:rollback --batch=nightly-import

# Live-follow new audit records (like tail -f)
php artisan recordkeeper:tail --model=Order --interval=3

# Audit stats dashboard
php artisan recordkeeper:stats --since="-30 days"

# Prune old records
php artisan recordkeeper:prune --days=365 --dry-run
php artisan recordkeeper:prune --days=365 --yes

# List discovered auditable models
php artisan recordkeeper:models
```

---

## Configuration

`config/recordkeeper.php` — all keys:

| Key | Default | Description |
|---|---|---|
| `enabled` | `true` | Global kill switch |
| `privacy.mode` | `redact` | `redact` \| `encrypt` \| `off` |
| `privacy.mask` | `***` | Redaction replacement value |
| `privacy.sensitive_patterns` | `[password, secret, token, ...]` | Auto-redacted field name patterns |
| `privacy.global_exclude` | `[remember_token]` | Always excluded from every audit |
| `rollback.enabled` | `true` | Enable rollback |
| `rollback.restore_deleted` | `true` | Allow restoring force-deleted records |
| `retention.default_days` | `0` | Prune audits older than N days (`0` = keep forever) |
| `retention.per_model` | `[]` | Per-model overrides: `['App\Models\Order' => 90]` |
| `queue.enabled` | `false` | Queue audit writes asynchronously |
| `jobs.enabled` | `false` | Track queued jobs |
| `commands.enabled` | `false` | Track Artisan commands |
| `commands.metrics.memory` | `true` | Record peak memory per command |
| `commands.metrics.audit_count` | `true` | Count model changes per command |
| `commands.metrics.anomaly` | `false` | Flag abnormal runs |
| `http.enabled` | `false` | Track outbound HTTP calls from jobs |
| `http.capture_headers` | `false` | Store request/response headers |
| `http.capture_body` | `false` | Store response body (truncated to `body_limit`) |
| `http.exclude_hosts` | `[]` | Skip hosts by pattern |
| `http.queue` | `false` | Write HTTP rows asynchronously |
| `strict` | `false` | Throw on failed audit writes (recommended in tests) |

---

## Database Schema

The extension migration adds to the `audits` table:

| Column | Type | Purpose |
|---|---|---|
| `guard` | `varchar` (indexed) | Auth guard — dedicated column for efficient filtering |
| `batch_id` | `varchar` (indexed) | Groups related audits |
| `context` | `json` | Route info, duration ms, command metrics, custom metadata |

The `audit_http_requests` table:

| Column | Type | Purpose |
|---|---|---|
| `audit_id` | `bigint` nullable | Links to parent job audit |
| `method` | `varchar(10)` | HTTP verb |
| `url` | `text` | Request URL |
| `status_code` | `int` nullable | Response status |
| `duration_ms` | `int` nullable | Round-trip time |
| `failed` | `boolean` | True if connection failed |
| `request_headers` | `json` nullable | Captured when `capture_headers = true` |
| `response_headers` | `json` nullable | Captured when `capture_headers = true` |
| `response_body` | `text` nullable | Captured when `capture_body = true` |

---

## Benchmarks

Run the PHPBench suite locally:

```bash
composer bench            # full suite
composer bench:quick      # fast smoke-run (3 revs × 2 iterations)
composer bench:http       # HttpTracker + HTTP listener
composer bench:command    # command metrics
composer bench:baseline   # store a baseline
composer bench:compare    # diff against baseline
```

Representative numbers on PHP 8.3 (SQLite in-memory, no opcache):

| Operation | Mean |
|---|---|
| `HttpTracker` set + clear context | ~0.03 µs |
| `HttpTracker` start + finish single request | ~0.11 µs |
| 10 concurrent tracked HTTP requests | ~1.0 µs |
| `AttributeResolver::resolve` (cached) | ~0.05 µs |
| `AttributeResolver::resolve` (cold, with `#[Redact]`) | ~4.4 µs |
| Write one `Audit` row | ~76 µs |
| Write one `AuditHttpRequest` row | ~62 µs |
| HTTP listener disabled (zero-cost skip) | ~10 µs |
| HTTP listener enabled, sync DB write | ~96 µs |
| HTTP listener, excluded host (skipped) | ~20 µs |
| Command audit, no metrics | ~121 µs |
| Command audit with memory + audit_count | ~264 µs |
| Command audit with anomaly detection | ~402 µs |

All features are **opt-in and zero-cost when disabled** — listeners are not even registered if the feature is off.

---

## Testing

```bash
composer test            # pest (no coverage)
composer test:coverage   # pest --coverage --coverage-clover coverage.xml
composer analyse         # phpstan
composer format          # pint
```

Test matrix:

| PHP | Laravel | Filament |
|---|---|---|
| 8.2 | 11 / 12 | ^5.0 |
| 8.3 | 11 / 12 | ^5.0 |
| 8.4 | 11 / 12 | ^5.0 |

---

## License

MIT — see [LICENSE](LICENSE).
