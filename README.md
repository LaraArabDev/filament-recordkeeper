<p align="center">
  <img src="art/banner.png" alt="Filament Recordkeeper" width="100%">
</p>

<h3 align="center">Production-grade audit trail for Laravel</h3>
<p align="center">PHP 8 attributes · Filament 5 resource · Zero-cost opt-in features</p>

<p align="center">
  <a href="https://github.com/LaraArabDev/filament-recordkeeper/actions/workflows/tests.yml"><img src="https://github.com/LaraArabDev/filament-recordkeeper/actions/workflows/tests.yml/badge.svg" alt="Tests"></a>
  <a href="https://github.com/LaraArabDev/filament-recordkeeper/actions/workflows/static-analysis.yml"><img src="https://github.com/LaraArabDev/filament-recordkeeper/actions/workflows/static-analysis.yml/badge.svg" alt="Static Analysis"></a>
  <a href="https://github.com/LaraArabDev/filament-recordkeeper/actions/workflows/code-style.yml"><img src="https://github.com/LaraArabDev/filament-recordkeeper/actions/workflows/code-style.yml/badge.svg" alt="Code Style"></a>
  <a href="https://github.com/LaraArabDev/filament-recordkeeper/actions/workflows/load-test.yml"><img src="https://github.com/LaraArabDev/filament-recordkeeper/actions/workflows/load-test.yml/badge.svg" alt="Load Tests"></a>
  <br>
  <a href="https://codecov.io/gh/LaraArabDev/filament-recordkeeper"><img src="https://codecov.io/gh/LaraArabDev/filament-recordkeeper/graph/badge.svg" alt="Coverage"></a>
  <a href="https://scorecard.dev/viewer/?uri=github.com/LaraArabDev/filament-recordkeeper"><img src="https://api.scorecard.dev/projects/github.com/LaraArabDev/filament-recordkeeper/badge" alt="OpenSSF Scorecard"></a>
  <a href="https://github.com/LaraArabDev/filament-recordkeeper/actions/workflows/benchmarks.yml"><img src="https://img.shields.io/badge/benchmark-phpbench-blue" alt="PHPBench"></a>
  <a href="LICENSE"><img src="https://img.shields.io/badge/License-MIT-yellow.svg" alt="MIT"></a>
</p>

---

**Filament Recordkeeper** builds on [`owen-it/laravel-auditing`](https://laravel-auditing.com/) and extends it into a full observability platform. It doesn't reinvent auditing — it supercharges it.

laravel-auditing tracks model changes. Recordkeeper extends that to everything:

| Event | What is recorded |
|---|---|
| Model created / updated / deleted | Eloquent diff via laravel-auditing |
| Route or API request | Guard, actor, method, duration, status |
| Queued job | Start, pass/fail, duration |
| Artisan command | Exit code, peak memory, audit impact, anomaly flag |
| Outbound HTTP from a job | URL, status, duration, linked to parent job |
| Rollback | Recorded as its own audit event |

Everything lands in one `audits` table. Query it with a fluent builder, revert changes from the CLI or Filament UI, prune on a schedule, and protect sensitive fields with redaction or field-level encryption — all via PHP 8 attributes.

**Filament 5 is optional.** The core runs headless. The plugin adds a diff viewer, timeline, stats widget, and a permission-gated revert button.

---

## Requirements

| Dependency | Version |
|---|---|
| PHP | 8.2 · 8.3 · 8.4 |
| Laravel | 11 · 12 |
| owen-it/laravel-auditing | ^13 \| ^14 |
| Filament | ^5.0 *(optional)* |

---

## Installation

```bash
composer require laraarabdev/filament-recordkeeper
php artisan recordkeeper:install
php artisan migrate
```

The install wizard publishes config and migrations, skipping files that already exist. To overwrite:

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

## Features

### Model Attributes

| Attribute | What it does |
|---|---|
| `#[Auditable]` | Enable auditing; accepts `events`, `retentionDays`, `threshold`, `tags` |
| `#[AuditExclude('field')]` | Exclude a field from all audit records |
| `#[Redact('field')]` | Replace the field value with `***` at write time |
| `#[Encrypt('field')]` | AES-encrypt the field in the audit (auto-decrypted on rollback) |
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

### Route Auditing

```php
// Web route — stores guard = 'web', resolves auth()->user()
Route::middleware('audit')->post('/pay', PayController::class);

// API route — stores guard = 'api', resolves actor from token
Route::middleware(['auth:sanctum', 'audit.api'])->apiResource('orders', OrderApiController::class);
```

Each request creates an `Audit` row with the route name, HTTP method, response status, duration in ms, and the authenticated actor.

### Job & Command Auditing

Enable in config:

```php
// config/recordkeeper.php
'jobs'     => ['enabled' => true],
'commands' => [
    'enabled' => true,
    'metrics' => [
        'memory'      => true,   // record peak memory
        'audit_count' => true,   // count model changes triggered
        'anomaly'     => true,   // flag runs that spike above historical average
    ],
],
```

Command audits include structured context:

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

### Outbound HTTP Tracking

When a job calls an external API (Stripe, HubSpot, etc.), Recordkeeper links those requests to the parent job audit:

```php
'http' => [
    'enabled'         => true,
    'capture_headers' => true,
    'capture_body'    => false,
    'exclude_hosts'   => ['internal.example.com'],
],
```

Requests are stored in `audit_http_requests` and shown as a tab in the Filament audit detail page.

### Rollback

```php
// Roll back a single audit (dry-run first)
$audit = $order->audits()->rollbackable()->latest('id')->first();
$preview = $audit->rollback(dryRun: true);
$audit->rollback();

// Roll back an entire batch atomically
Recordkeeper::rollbackBatch('nightly-import');

// Roll back by ID
Recordkeeper::rollback($auditId);
```

Handles encrypted fields, `SoftDeletes`, and sequential re-rollback automatically.

### Batch Auditing

```php
Recordkeeper::batch('nightly-import-2025-01', function () {
    Order::create([...]);
    Order::create([...]);
    // All audits share batch_id = 'nightly-import-2025-01'
});

Recordkeeper::rollbackBatch('nightly-import-2025-01');
```

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
| `->model(string)` | Short name or FQCN |
| `->event(string\|array)` | Filter by event name(s) |
| `->actor(id, type?)` | Filter by `user_id` + optional `user_type` |
| `->guard(string)` | Filter by auth guard |
| `->tag(string\|array)` | Filter by tag(s) |
| `->batch(string)` | Filter by `batch_id` |
| `->between(from, until)` | Date range |
| `->since(from)` | Created after date |
| `->rollbackable()` | Only model-change events |
| `->search(string)` | Full-text search |
| `->latest()` | Newest first |
| `->builder()` | Return the underlying Eloquent Builder |

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

### Manual Audit Log

```php
// System event with no model
Recordkeeper::log('payment.gateway.timeout', context: [
    'gateway' => 'stripe',
    'attempt' => 3,
]);

// Event against a specific model
Recordkeeper::log('export.triggered', subject: $order, context: ['format' => 'csv']);
```

### Filament — Add Audit History to Any Resource

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

## CLI Reference

```bash
# Publish config and migrations
php artisan recordkeeper:install

# Search with filters
php artisan recordkeeper:search --model=Order --event=updated --since="-7 days" --json

# Show a single audit with full diff
php artisan recordkeeper:show 1842

# Roll back — preview first, then apply
php artisan recordkeeper:rollback 1842 --dry-run
php artisan recordkeeper:rollback 1842 --yes

# Roll back an entire batch
php artisan recordkeeper:rollback --batch=nightly-import

# Live-follow new records (tail -f style)
php artisan recordkeeper:tail --model=Order --interval=3

# Stats dashboard
php artisan recordkeeper:stats --since="-30 days"

# Prune old records
php artisan recordkeeper:prune --days=365 --dry-run
php artisan recordkeeper:prune --days=365 --yes

# List all discovered auditable models
php artisan recordkeeper:models
```

---

## Configuration

`config/recordkeeper.php`:

| Key | Default | Description |
|---|---|---|
| `enabled` | `true` | Global kill switch |
| `privacy.mode` | `redact` | `redact` \| `encrypt` \| `off` |
| `privacy.mask` | `***` | Redaction replacement |
| `privacy.sensitive_patterns` | `[password, secret, token …]` | Auto-redacted field name patterns |
| `rollback.enabled` | `true` | Enable rollback |
| `rollback.restore_deleted` | `true` | Allow restoring soft-deleted records |
| `retention.default_days` | `0` | Prune after N days (`0` = keep forever) |
| `queue.enabled` | `false` | Queue audit writes asynchronously |
| `jobs.enabled` | `false` | Track queued jobs |
| `commands.enabled` | `false` | Track Artisan commands |
| `commands.metrics.memory` | `true` | Record peak memory |
| `commands.metrics.audit_count` | `true` | Count model changes per command |
| `commands.metrics.anomaly` | `false` | Flag abnormal runs |
| `http.enabled` | `false` | Track outbound HTTP from jobs |
| `http.capture_headers` | `false` | Store request/response headers |
| `http.capture_body` | `false` | Store response body |
| `http.exclude_hosts` | `[]` | Skip hosts by pattern |
| `strict` | `false` | Throw on failed writes (recommended in tests) |

---

## Database Schema

**`audits` table** (extends laravel-auditing):

| Column | Type | Purpose |
|---|---|---|
| `guard` | `varchar` (indexed) | Auth guard — dedicated column for fast filtering |
| `batch_id` | `varchar` (indexed) | Groups related audits |
| `context` | `json` | Route info, duration, command metrics, custom metadata |

**`audit_http_requests` table:**

| Column | Type | Purpose |
|---|---|---|
| `audit_id` | `bigint` nullable | Links to parent job audit |
| `method` | `varchar(10)` | HTTP verb |
| `url` | `text` | Request URL |
| `status_code` | `int` nullable | Response status |
| `duration_ms` | `int` nullable | Round-trip time |
| `failed` | `boolean` | `true` if connection failed |
| `request_headers` | `json` nullable | When `capture_headers = true` |
| `response_headers` | `json` nullable | When `capture_headers = true` |
| `response_body` | `text` nullable | When `capture_body = true` |

---

## Benchmarks

```bash
composer bench            # full suite
composer bench:quick      # 3 revs × 2 iterations
composer bench:http       # HttpTracker + HTTP listener
composer bench:command    # command metrics
composer bench:baseline   # store baseline
composer bench:compare    # diff against baseline
```

Representative numbers — PHP 8.3, SQLite in-memory, no opcache:

| Operation | Mean |
|---|---|
| `HttpTracker` set + clear context | ~0.03 µs |
| `HttpTracker` start + finish one request | ~0.11 µs |
| 10 concurrent tracked requests | ~1.0 µs |
| `AttributeResolver::resolve` (cached) | ~0.05 µs |
| `AttributeResolver::resolve` (cold, `#[Redact]`) | ~4.4 µs |
| Write one `Audit` row | ~76 µs |
| Write one `AuditHttpRequest` row | ~62 µs |
| HTTP listener disabled (zero-cost skip) | ~10 µs |
| HTTP listener enabled, sync write | ~96 µs |
| HTTP listener, excluded host | ~20 µs |
| Command audit, no metrics | ~121 µs |
| Command audit with memory + audit_count | ~264 µs |
| Command audit with anomaly detection | ~402 µs |

All features are **zero-cost when disabled** — listeners are not registered unless the feature flag is on.

---

## Testing

```bash
composer test            # pest
composer test:coverage   # pest --coverage
composer analyse         # phpstan
composer format          # pint
```

Test matrix:

| PHP | Laravel | Filament |
|---|---|---|
| 8.2 | 11 · 12 | ^5.0 |
| 8.3 | 11 · 12 | ^5.0 |
| 8.4 | 11 · 12 | ^5.0 |

---

## License

MIT — see [LICENSE](LICENSE).
