# Filament Recordkeeper

Filament 5 admin UI for [`laraarabdev/recordkeeper`](https://github.com/LaraArabDev/recordkeeper)
— an audit resource with smart filters, a before/after diff viewer, an audit timeline, a stats
widget, and permission-gated rollback.

> Only need headless audit + rollback (API / no admin panel)? Install
> [`laraarabdev/recordkeeper`](https://github.com/LaraArabDev/recordkeeper) directly — this
> package is just the Filament layer on top of it.

## Install

```bash
composer require laraarabdev/filament-recordkeeper
php artisan recordkeeper:install
php artisan migrate
```

Register the plugin in your panel provider:

```php
use LaraArabDev\FilamentRecordkeeper\RecordkeeperPlugin;

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

Add audit history to any resource:

```php
use LaraArabDev\FilamentRecordkeeper\Concerns\HasAuditHistory;

class OrderResource extends Resource
{
    use HasAuditHistory;
}
```

All auditing, attributes, rollback, query builder, commands, and middleware come from the core
`laraarabdev/recordkeeper` package. See its README for model setup, PHP attributes, and CLI.

## License

MIT.
