# Filament Recordkeeper

[![Tests](https://github.com/LaraArabDev/filament-recordkeeper/actions/workflows/tests.yml/badge.svg)](https://github.com/LaraArabDev/filament-recordkeeper/actions/workflows/tests.yml)
[![Static Analysis](https://github.com/LaraArabDev/filament-recordkeeper/actions/workflows/static-analysis.yml/badge.svg)](https://github.com/LaraArabDev/filament-recordkeeper/actions/workflows/static-analysis.yml)
[![Code Style](https://github.com/LaraArabDev/filament-recordkeeper/actions/workflows/code-style.yml/badge.svg)](https://github.com/LaraArabDev/filament-recordkeeper/actions/workflows/code-style.yml)
[![PHPBench](https://img.shields.io/badge/benchmark-phpbench-blue)](https://github.com/LaraArabDev/filament-recordkeeper/actions/workflows/benchmarks.yml)
[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](LICENSE)

A Laravel package that wraps and extends [`owen-it/laravel-auditing`](https://laravel-auditing.com/) to provide:

- **PHP 8 attribute configuration** — `#[Auditable]`, `#[Redact]`, `#[Encrypt]`, `#[AuditExclude]`, `#[Audit]`
- **Route + API auditing** — HTTP middleware that writes `Audit` records for web and API routes
- **Rollback with batch support** — revert any audit or an entire batch, with SoftDeletes support and dry-run mode
- **Sensitive-data protection** — pattern-based redaction and field-level encryption at write time
- **Fluent audit query builder** — chainable `AuditQuery` for filtering by model, actor, guard, tag, batch, and date
- **Audit model scopes** — ergonomic `Audit::forGuard()`, `forModel()`, `forActor()`, `rollbackable()`, and more
- **Date-based pruning** — `MassPrunable` retention via `recordkeeper:prune` or Laravel's scheduler
- **Optional Filament 5 resource** — smart filters, before/after diff viewer, audit timeline, and permission-gated revert

## Requirements

| Dependency | Version |
|---|---|
| PHP | 8.2 / 8.3 / 8.4 |
| Laravel | 11 / 12 |
| `owen-it/laravel-auditing` | ^13.0 |
| Filament | ^5.0 *(optional)* |

## Installation

```bash
composer require laraarabdev/filament-recordkeeper
php artisan recordkeeper:install
php artisan migrate
```

The install command publishes:
- `config/recordkeeper.php` — this package's config
- `database/migrations/*_add_recordkeeper_columns_to_audits_table.php` — adds `guard`, `batch_id`, `context` columns + indexes
- `config/audit.php` — laravel-auditing config *(skipped if already exists)*
- `database/migrations/*_create_audits_table.php` — laravel-auditing base migration *(skipped if already run)*

To re-publish any file (e.g. after an upgrade):

```bash
php artisan recordkeeper:install --force
```

### Mode A — Filament Panel

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

### Mode B — Headless / API only

Add the trait and PHP attributes to your models:

```php
use LaraArabDev\Recordkeeper\Attributes\Auditable;
use LaraArabDev\Recordkeeper\Attributes\Redact;
use LaraArabDev\Recordkeeper\Attributes\Encrypt;
use LaraArabDev\Recordkeeper\Concerns\AuditsChanges;

#[Auditable(events: ['created', 'updated', 'deleted'], retentionDays: 365)]
#[Redact('discount_code')]
#[Encrypt('national_id')]
class Order extends Model implements \OwenIt\Auditing\Contracts\Auditable
{
    use AuditsChanges;
    use SoftDeletes;
}
```

## Usage

### PHP Attributes

| Attribute | Description |
|---|---|
| `#[Auditable]` | Enable auditing; accepts `events`, `retentionDays`, `threshold`, `tags` |
| `#[AuditExclude('field')]` | Exclude one or more fields from audit records |
| `#[Redact('field')]` | Replace field value with `***` in audit records |
| `#[Encrypt('field')]` | Encrypt field value in audit records (decrypted automatically on rollback) |
| `#[Audit('event')]` | Fire a custom named audit event |

```php
#[Auditable(events: ['created', 'updated', 'deleted'], tags: ['orders'])]
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
// Web routes — stores guard = 'web'
Route::middleware('audit:tag=finance,body=true')->post('/pay', PayController::class);

// API routes — stores guard = 'api', resolves actor from token
Route::middleware(['auth:sanctum', 'audit.api'])->apiResource('orders', OrderApiController::class);
```

Each request creates an `Audit` row with:
- `event` = `route.<METHOD>` (e.g. `route.POST`)
- `guard` = the auth guard used
- `context` = route name, HTTP method, response status, duration in ms
- `user_id` / `user_type` = the resolved actor

### Rollback

```php
// Roll back the most recent audit for a record
$audit = $order->audits()->rollbackable()->latest('id')->first();
$audit->rollback();

// Dry-run — returns a preview without writing changes
$preview = $audit->rollback(dryRun: true);

// Roll back an entire batch in a transaction (newest audit first)
Recordkeeper::rollbackBatch('nightly-import');

// Via facade by ID
Recordkeeper::rollback($auditId);
```

Rollback handles:
- Models with `#[Redact]` / `#[Encrypt]` — values are decrypted before restoring
- SoftDeletes — restores the soft-deleted record, or recreates it if force-deleted
- Sequential rollbacks — each rollback deletes its own audit row so re-rolling works correctly

### Fluent Query Builder

```php
use LaraArabDev\Recordkeeper\Support\AuditQuery;

$results = app(AuditQuery::class)
    ->model('Order')
    ->event(['created', 'updated'])
    ->actor(42, 'Admin')
    ->guard('api')
    ->tag('finance')
    ->batch('nightly-import')
    ->since('-7 days')
    ->rollbackable()
    ->latest()
    ->limit(50)
    ->builder()
    ->get();
```

| Method | Description |
|---|---|
| `->model(string)` | Filter by model class (short name or FQCN) |
| `->subjectId(int\|string)` | Filter by `auditable_id` |
| `->event(string\|array)` | Filter by event name(s) |
| `->rollbackable()` | Limit to created / updated / deleted / restored |
| `->actor(id, type?)` | Filter by `user_id` and optional `user_type` |
| `->actorType(string)` | Filter by `user_type` only |
| `->onlyAuthenticated()` | Exclude system / guest audits |
| `->guard(string)` | Filter by auth guard |
| `->tag(string\|array)` | Filter by tag(s) |
| `->batch(string)` | Filter by `batch_id` |
| `->between(from, until)` | Date range filter |
| `->since(from)` | Created after date |
| `->search(string)` | Search across event, auditable type, batch, user |
| `->latest()` | Order by `created_at` desc |
| `->limit(int)` | Limit results |
| `->offset(int)` | Offset results |
| `->builder()` | Return the underlying Eloquent Builder |

### Audit Model Scopes

```php
use LaraArabDev\Recordkeeper\Models\Audit;

Audit::forGuard('api')->get();
Audit::forModel('Order')->latest()->get();
Audit::forSubject($order)->get();
Audit::forActor($adminUser)->get();
Audit::forActor(42, 'Admin')->get();
Audit::forActorType('Admin')->get();
Audit::forBatch('nightly-import')->get();
Audit::rollbackable()->latest('id')->get();
Audit::routeHits()->where('created_at', '>=', now()->subDay())->get();
```

### Batch Auditing

```php
Recordkeeper::batch('nightly-import-2025-01', function () {
    Order::create([...]);
    Order::create([...]);
    // All audit rows share batch_id = 'nightly-import-2025-01'
});

// Roll back the entire batch
Recordkeeper::rollbackBatch('nightly-import-2025-01');
```

### Per-model Audit Context

```php
$order->auditContext(['reason' => 'admin override', 'ticket' => 'JIRA-123'])
      ->update(['status' => 'refunded']);
```

### Manual Audit Log

```php
// System event with no model subject
Recordkeeper::log('payment.gateway.timeout', context: ['gateway' => 'stripe', 'attempt' => 3]);

// Event against a specific model
Recordkeeper::log('export.triggered', subject: $order, context: ['format' => 'csv']);
```

### Events

```php
use LaraArabDev\Recordkeeper\Events\ChangeRecorded;

// Fired after every audit write (model events and manual log calls)
Event::listen(ChangeRecorded::class, function (ChangeRecorded $event) {
    // $event->audit — the Audit model instance
});
```

### Add Audit History to a Filament Resource

```php
use LaraArabDev\Recordkeeper\Filament\Concerns\HasAuditHistory;

class OrderResource extends Resource
{
    use HasAuditHistory;
}
```

Or attach the relation manager directly:

```php
use LaraArabDev\Recordkeeper\Filament\RelationManagers\AuditsRelationManager;

public static function getRelationManagers(): array
{
    return [AuditsRelationManager::class];
}
```

### Custom Actor Resolver

```php
// In a service provider boot()
Recordkeeper::resolveActorUsing(fn () => auth('admin')->user() ?? auth('api')->user());
```

## CLI Commands

```bash
# Publish config and migrations
php artisan recordkeeper:install
php artisan recordkeeper:install --force    # overwrite existing files

# Search audits
php artisan recordkeeper:search --model=Order --event=updated --since="-7 days" --json

# Show a single audit with full diff
php artisan recordkeeper:show 1842

# Roll back (dry-run first, then apply)
php artisan recordkeeper:rollback 1842 --dry-run
php artisan recordkeeper:rollback 1842 --yes

# Roll back an entire batch
php artisan recordkeeper:rollback --batch=nightly-import

# Live-follow new audit records (like tail -f)
php artisan recordkeeper:tail --model=Order --interval=3

# Audit stats dashboard
php artisan recordkeeper:stats --since="-30 days"

# Prune old records (dry-run first, then apply)
php artisan recordkeeper:prune --days=365 --dry-run
php artisan recordkeeper:prune --days=365 --yes

# List discovered auditable models
php artisan recordkeeper:models
```

## Database Columns

The extension migration adds these columns to the `audits` table:

| Column | Type | Description |
|---|---|---|
| `guard` | `varchar` indexed | Auth guard (`web`, `api`, etc.) — stored as a dedicated column, not in JSON |
| `batch_id` | `varchar` indexed | Groups related audits from one logical operation |
| `context` | `json` nullable | Route info, duration ms, custom metadata |

Additional indexes on: `event`, `guard`, `batch_id`, `created_at`, `(auditable_type, auditable_id, event)`.

## Configuration

`config/recordkeeper.php`:

| Key | Default | Description |
|---|---|---|
| `enabled` | `true` | Global kill switch for all auditing |
| `privacy.mode` | `redact` | `redact` \| `encrypt` \| `off` |
| `privacy.mask` | `***` | Replacement value for redacted fields |
| `privacy.sensitive_patterns` | `[password, secret, token, ...]` | Auto-redacted field name patterns |
| `privacy.global_exclude` | `[remember_token]` | Always excluded from every audit |
| `rollback.enabled` | `true` | Enable / disable rollback |
| `rollback.restore_deleted` | `true` | Allow restoring deleted records |
| `rollback.permission` | `rollback_audits` | Gate permission for Filament revert button |
| `retention.default_days` | `0` | Delete audits older than N days (`0` = disabled) |
| `retention.per_model` | `[]` | Per-model overrides: `['App\Models\Order' => 90]` |
| `queue.enabled` | `false` | Queue audit writes asynchronously |
| `queue.connection` | `null` | Queue connection |
| `queue.queue` | `audits` | Queue name |
| `strict` | `false` | Throw on failed audit writes (enable in tests) |

### Date-based Retention

```bash
# Manual prune
php artisan recordkeeper:prune --days=365

# Or schedule via Laravel (routes/console.php)
Schedule::command('model:prune', ['--model' => \LaraArabDev\Recordkeeper\Models\Audit::class])->daily();
```

## Benchmarks

Run the PHPBench suite locally:

```bash
composer bench            # full suite
composer bench:quick      # fast smoke-run (3 revs × 2 iterations)
composer bench:http       # HttpTracker + HTTP listener only
composer bench:command    # command metrics only
```

Representative numbers on PHP 8.2 (SQLite in-memory, no opcache):

| Subject | Mean |
|---|---|
| `HttpTracker::setContext` + `clearContext` | ~0.03 µs |
| `HttpTracker::startRequest` + `finishRequest` | ~0.11 µs |
| 10 concurrent tracked requests | ~1.0 µs |
| `AttributeResolver::resolve` (cached) | ~0.05 µs |
| `AttributeResolver::resolve` (cold, with `#[Redact]`) | ~4.4 µs |
| Write one `Audit` row | ~76 µs |
| Write one `AuditHttpRequest` row | ~62 µs |
| HTTP listener disabled (event fires, listener skips) | ~10 µs |
| HTTP listener enabled, sync DB write | ~96 µs |
| HTTP listener, excluded host (skipped) | ~20 µs |
| Command audit without metrics | ~121 µs |
| Command audit with memory + audit_count | ~264 µs |
| Command audit with anomaly detection | ~402 µs |

Store a baseline and compare across branches:

```bash
composer bench:baseline   # tag current run as "baseline"
composer bench:compare    # diff current run against baseline
```

## Testing

```bash
composer test
composer test:coverage
composer analyse
composer format
```

Test matrix:

| PHP | Laravel | Filament |
|---|---|---|
| 8.2 | 11 / 12 | ^5.0 |
| 8.3 | 11 / 12 | ^5.0 |
| 8.4 | 11 / 12 | ^5.0 |

## License

MIT — see [LICENSE](LICENSE).
