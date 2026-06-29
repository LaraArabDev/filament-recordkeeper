# Filament Recordkeeper

[![Tests](https://github.com/LaraArabDev/filament-recordkeeper/actions/workflows/tests.yml/badge.svg)](https://github.com/LaraArabDev/filament-recordkeeper/actions/workflows/tests.yml)
[![Static Analysis](https://github.com/LaraArabDev/filament-recordkeeper/actions/workflows/static-analysis.yml/badge.svg)](https://github.com/LaraArabDev/filament-recordkeeper/actions/workflows/static-analysis.yml)
[![Code Style](https://github.com/LaraArabDev/filament-recordkeeper/actions/workflows/code-style.yml/badge.svg)](https://github.com/LaraArabDev/filament-recordkeeper/actions/workflows/code-style.yml)
[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](LICENSE)

A Laravel package that wraps and extends [`owen-it/laravel-auditing`](https://laravel-auditing.com/) to provide:

- **PHP 8 attribute configuration** — `#[Auditable]`, `#[Redact]`, `#[Encrypt]`, `#[AuditExclude]`, `#[Audit]`
- **Route + API auditing** — HTTP middleware that writes `Audit` records for web and API routes
- **One-click rollback** — built on laravel-auditing's `transitionTo()` with batch support
- **Sensitive-data protection** — redaction and encryption via laravel-auditing Attribute Modifiers
- **Optional Filament 5 resource** — smart filters, before/after diff viewer, and permission-gated revert

## Requirements

- PHP 8.2 / 8.3 / 8.4
- Laravel 11 / 12
- `owen-it/laravel-auditing` ^13
- Filament 5 *(optional)*

## Installation

```bash
composer require laraarabdev/filament-recordkeeper
php artisan recordkeeper:install
php artisan migrate
```

### Mode A — Filament Panel (full experience)

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

### Mode B — Headless / API only (no Filament)

Add the trait + attributes to your models:

```php
use LaraArabDev\Recordkeeper\Attributes\Auditable;
use LaraArabDev\Recordkeeper\Attributes\Redact;
use LaraArabDev\Recordkeeper\Concerns\AuditsChanges;

#[Auditable]
#[Redact('cvv')]
class Payment extends Model implements \OwenIt\Auditing\Contracts\Auditable
{
    use AuditsChanges;
}
```

## Usage

### Model Auditing

```php
#[Auditable(events: ['created', 'updated', 'deleted'], retentionDays: 365)]
#[AuditExclude('internal_notes')]
#[Redact('discount_code')]
#[Encrypt('national_id')]
class Order extends Model implements \OwenIt\Auditing\Contracts\Auditable
{
    use AuditsChanges;
    use SoftDeletes;
}
```

### Route Auditing

```php
// Web routes
Route::middleware('audit:tag=finance,body=true')->post('/pay', PayController::class);

// API routes (resolves actor from token guard)
Route::middleware(['auth:sanctum', 'audit.api'])->apiResource('orders', OrderApiController::class);
```

### Rollback

```php
// Roll back a single audit
$order->audits()->latest()->first()->rollback();

// Dry-run first
$diff = $order->audits()->latest()->first()->rollback(dryRun: true);

// Roll back a whole batch (newest-first, in a transaction)
Recordkeeper::rollbackBatch('nightly-import');
```

### Per-model audit context

```php
$order->auditContext(['reason' => 'admin override', 'ticket' => 'JIRA-123'])
      ->update(['status' => 'refunded']);
```

### Batch auditing

```php
Recordkeeper::batch('nightly-import', function () {
    Order::create([...]);
    Order::create([...]);
    // All audit rows share batch_id = 'nightly-import'
});
```

### Add audit history to any Filament resource

```php
use LaraArabDev\Recordkeeper\Filament\Concerns\HasAuditHistory;

class OrderResource extends Resource
{
    use HasAuditHistory;
    // ...
}
```

## CLI Commands

```bash
# Search audits
php artisan recordkeeper:search --model=Order --event=updated --since="-7 days" --json

# Show a single audit with diff
php artisan recordkeeper:show 1842

# Roll back (dry-run first)
php artisan recordkeeper:rollback 1842 --dry-run
php artisan recordkeeper:rollback 1842 --yes

# Roll back an entire batch
php artisan recordkeeper:rollback --batch=nightly-import

# Live-follow new audits
php artisan recordkeeper:tail --model=Order --interval=3

# Stats dashboard
php artisan recordkeeper:stats --since="-30 days"

# Prune old records
php artisan recordkeeper:prune --days=365 --dry-run
php artisan recordkeeper:prune --days=365 --yes

# List auditable models
php artisan recordkeeper:models
```

## Configuration

`config/recordkeeper.php` (published by `recordkeeper:install`):

| Key | Default | Description |
|-----|---------|-------------|
| `enabled` | `true` | Kill switch for all auditing |
| `privacy.mode` | `redact` | `redact` \| `encrypt` \| `off` |
| `privacy.sensitive_patterns` | `[password, secret, token, ...]` | Auto-redacted field patterns |
| `rollback.enabled` | `true` | Enable/disable rollback |
| `rollback.permission` | `rollback_audits` | Gate permission name |
| `retention.default_days` | `365` | Default audit retention |
| `queue.enabled` | `false` | Queue audit writes asynchronously |

Laravel-auditing's own `config/audit.php` governs drivers and resolvers — Recordkeeper layers on top.

## License

MIT — see [LICENSE](LICENSE).
# filament-recordkeeper
