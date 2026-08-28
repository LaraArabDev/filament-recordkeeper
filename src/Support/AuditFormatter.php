<?php

declare(strict_types=1);

namespace LaraArabDev\RecordkeeperFilament\Support;

use Filament\Tables\Actions\Action;
use LaraArabDev\Recordkeeper\Models\Audit;
use LaraArabDev\RecordkeeperFilament\RecordkeeperPlugin;

/**
 * Static helpers for formatting audit data in Filament tables and infolists.
 */
final class AuditFormatter
{
    /** @var object{total: int, created: int, updated: int, deleted: int, routes: int}|null */
    private static ?object $eventCountsCache = null;

    /**
     * Map an audit event name to a Filament badge color.
     *
     * @param  string  $event  The audit event name.
     * @return string Filament color identifier (success, warning, danger, info, gray).
     */
    public static function eventColor(string $event): string
    {
        return match ($event) {
            'created' => 'success',
            'updated' => 'warning',
            'deleted', 'forceDeleted' => 'danger',
            default => str_starts_with($event, 'route.') ? 'info' : 'gray',
        };
    }

    /**
     * Format the actor (user) as a human-readable label.
     *
     * @param  mixed  $userId  The user ID or null for system actions.
     * @param  mixed  $userType  The polymorphic user type FQCN.
     * @return string Label like "User #5" or "system".
     */
    public static function actorLabel(mixed $userId, mixed $userType): string
    {
        if ($userId === null || $userId === 0 || $userId === '') {
            return 'system';
        }

        return class_basename((string) ($userType ?? 'User')).' #'.$userId;
    }

    /**
     * Format the auditable subject as a human-readable label.
     *
     * @param  mixed  $auditableType  The polymorphic auditable type FQCN.
     * @param  mixed  $auditableId  The auditable model ID.
     * @return string Label like "Order #42" or an em dash when no type is present.
     */
    public static function subjectLabel(mixed $auditableType, mixed $auditableId): string
    {
        return $auditableType
            ? class_basename((string) $auditableType).' #'.$auditableId
            : '—';
    }

    /**
     * Aggregate event counts across all audits in a single query (memoized per request).
     *
     * @return object{total: int, created: int, updated: int, deleted: int, routes: int}
     */
    public static function eventCounts(): object
    {
        if (self::$eventCountsCache !== null) {
            return self::$eventCountsCache;
        }

        $row = Audit::query()
            ->selectRaw(implode(', ', [
                'COUNT(*) as total',
                "SUM(CASE WHEN event = 'created' THEN 1 ELSE 0 END) as created",
                "SUM(CASE WHEN event = 'updated' THEN 1 ELSE 0 END) as updated",
                "SUM(CASE WHEN event IN ('deleted','forceDeleted') THEN 1 ELSE 0 END) as deleted",
                "SUM(CASE WHEN event LIKE 'route.%' THEN 1 ELSE 0 END) as routes",
            ]))
            ->first();

        return self::$eventCountsCache = (object) [
            'total' => (int) $row->total,
            'created' => (int) $row->created,
            'updated' => (int) $row->updated,
            'deleted' => (int) $row->deleted,
            'routes' => (int) $row->routes,
        ];
    }

    /**
     * Clear the memoized event counts cache.
     */
    public static function resetEventCountsCache(): void
    {
        self::$eventCountsCache = null;
    }

    /**
     * Build a reusable Filament table action for reverting an audit.
     *
     * @return Action Configured revert action with confirmation and visibility check.
     */
    public static function revertAction(): Action
    {
        return Action::make('revert')
            ->label('Revert')
            ->icon('heroicon-o-arrow-uturn-left')
            ->color('warning')
            ->requiresConfirmation()
            ->visible(fn (Audit $record) => self::canRevert($record))
            ->action(fn (Audit $record) => $record->rollback());
    }

    /**
     * Determine whether an audit record can be reverted in the current context.
     *
     * @param  Audit  $audit  The audit record to check.
     * @return bool True if the audit is rollbackable, core config allows it, and the plugin has rollback enabled.
     */
    public static function canRevert(Audit $audit): bool
    {
        if (! $audit->isRollbackable() || ! config('recordkeeper.rollback.enabled', true)) {
            return false;
        }

        try {
            return RecordkeeperPlugin::get()->isRollbackEnabled();
        } catch (\Exception) {
            return false;
        }
    }
}
