<p align="center">
    <img src="art/banner.svg" alt="Filament Recordkeeper Banner" style="width: 100%; max-width: 800px;">
</p>

<h1 align="center">Filament Recordkeeper</h1>

<p align="center">
    <strong>Admin UI for audit trails, rollback & data protection in Filament</strong>
</p>

<p align="center">
    <a href="https://packagist.org/packages/laraarabdev/filament-recordkeeper"><img src="https://img.shields.io/packagist/v/laraarabdev/filament-recordkeeper.svg?style=flat-square" alt="Latest Version"></a>
    <a href="https://packagist.org/packages/laraarabdev/filament-recordkeeper"><img src="https://img.shields.io/packagist/dt/laraarabdev/filament-recordkeeper.svg?style=flat-square" alt="Total Downloads"></a>
    <a href="https://packagist.org/packages/laraarabdev/filament-recordkeeper"><img src="https://img.shields.io/packagist/l/laraarabdev/filament-recordkeeper.svg?style=flat-square" alt="License"></a>
    <a href="https://packagist.org/packages/laraarabdev/filament-recordkeeper"><img src="https://img.shields.io/packagist/php-v/laraarabdev/filament-recordkeeper.svg?style=flat-square" alt="PHP"></a>
    <a href="https://filamentphp.com"><img src="https://img.shields.io/badge/filament-5.x-orange?style=flat-square" alt="Filament"></a>
</p>

<p align="center">
    A Filament 5 plugin that gives you a full admin UI on top of
    <a href="https://github.com/LaraArabDev/recordkeeper"><code>laraarabdev/recordkeeper</code></a>
    — browse audits, view diffs, rollback changes, and monitor command performance from your panel.<br>
    PHP 8.2 - 8.4 · Laravel 11 / 12 · Filament 5
</p>

<p align="center">
    <b><a href="https://github.com/LaraArabDev">LaraArabDev</a></b> — We build, develop, empower, and contribute. An Arab open-source community crafting production-grade Laravel packages.
</p>

<p align="center">
    <a href="#install">Install</a> ·
    <a href="#plugin-api">Plugin API</a> ·
    <a href="#widgets">Widgets</a> ·
    <a href="#relation-manager">Relation Manager</a>
</p>

---

## What's Included

| Component | Description |
| --- | --- |
| **Audit Resource** | Paginated table with event tabs (all, created, updated, deleted, routes), search, and filters |
| **View Page** | Before/after diff viewer with color-coded changes and permission-gated rollback |
| **Stats Widget** | Dashboard cards — total audits, created, updated, deleted, route hits, distinct actors |
| **Timeline Widget** | Recent audit activity feed for the dashboard |
| **Command Metrics** | Bar chart of command duration + audit impact with anomaly highlighting |
| **Relation Manager** | Embeddable audit history tab for any resource |
| **HasAuditHistory** | One-line trait to add the relation manager to your resource |

> Only need headless audit + rollback (API / no admin panel)? Install
> [`laraarabdev/recordkeeper`](https://github.com/LaraArabDev/recordkeeper) directly — this
> package is just the Filament layer on top of it.

---

## Install

```bash
composer require laraarabdev/filament-recordkeeper
php artisan recordkeeper:install
php artisan migrate
```

Register the plugin in your panel provider:

```php
use LaraArabDev\RecordkeeperFilament\RecordkeeperPlugin;

public function panel(Panel $panel): Panel
{
    return $panel->plugins([
        RecordkeeperPlugin::make(),
    ]);
}
```

That's it — the audit resource is now available in your panel.

---

## Plugin API

Configure everything through a fluent builder:

```php
RecordkeeperPlugin::make()
    ->enableRollback()              // show rollback action on audit view
    ->enableStatsWidget()           // register stats overview widget
    ->enableTimeline()              // register timeline widget
    ->enableCommandMetrics()        // register command performance chart
    ->navigationGroup('Audit')      // sidebar group label
    ->navigationSort(50)            // sidebar sort order
    ->navigationIcon('heroicon-o-shield-check')  // sidebar icon
    ->cluster(AuditCluster::class)  // assign to a Filament cluster
    ->pollingInterval('30s')        // live-refresh widgets
```

| Method | Default | Description |
| --- | --- | --- |
| `enableRollback()` | `false` | Show rollback button on audit detail page |
| `enableStatsWidget()` | `false` | Register stats overview dashboard widget |
| `enableTimeline()` | `false` | Register recent activity timeline widget |
| `enableCommandMetrics()` | `false` | Register command performance chart widget |
| `navigationGroup(string)` | `'Audit'` | Sidebar group for the audit resource |
| `navigationSort(int)` | `100` | Sort order in the sidebar |
| `navigationIcon(string)` | `'heroicon-o-clock'` | Icon for the audit resource |
| `cluster(string)` | `null` | Assign resource to a Filament cluster |
| `pollingInterval(string)` | `null` | Livewire polling interval (e.g. `'30s'`) |

---

## Widgets

### Stats Overview

Six stat cards: total audits, created, updated, deleted, route hits, and distinct actors.

```php
RecordkeeperPlugin::make()
    ->enableStatsWidget()
```

### Audit Timeline

A compact table showing the most recent audit entries with event badges, actor, and subject.

```php
RecordkeeperPlugin::make()
    ->enableTimeline()
```

### Command Metrics

A dual-axis bar chart showing command duration (ms) and audit impact across recent runs, with anomaly highlighting in red.

```php
RecordkeeperPlugin::make()
    ->enableCommandMetrics()
```

Only appears when command metrics are enabled in core config (`recordkeeper.commands.metrics`).

---

## Relation Manager

Add audit history to any Filament resource with one trait:

```php
use LaraArabDev\RecordkeeperFilament\Concerns\HasAuditHistory;

class OrderResource extends Resource
{
    use HasAuditHistory;
}
```

This adds an "Audits" tab to the resource's relation managers, showing all audit records for that model with event badges, actor info, and a link to the full audit view.

---

## Prerequisites

| Requirement | Version |
| --- | --- |
| PHP | 8.2, 8.3, or 8.4 |
| Laravel | 11 or 12 |
| Filament | 5.x |
| laraarabdev/recordkeeper | ^1.0 (auto-installed) |

---

## Core Package

All auditing logic, PHP 8 attributes, privacy controls, rollback engine, Artisan commands, and storage drivers come from the core [`laraarabdev/recordkeeper`](https://github.com/LaraArabDev/recordkeeper) package. See its README for:

- Model setup with `AuditsChanges` trait
- PHP 8 Attributes (`#[Auditable]`, `#[Redact]`, `#[Encrypt]`)
- Route & API auditing middleware
- Job, command, and event tracking
- Rollback API and batch operations
- 8 Artisan commands
- Storage drivers and configuration

---

## Testing

```bash
composer test
```

---

## Security

Please review [our security policy](SECURITY.md) on how to report security vulnerabilities.

---

## Credits & License

- [LaraArabDev](https://github.com/LaraArabDev)
- [All Contributors](../../contributors)

MIT License — see [LICENSE](LICENSE) for details.
