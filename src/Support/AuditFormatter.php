<?php

declare(strict_types=1);

namespace LaraArabDev\RecordkeeperFilament\Support;

use Filament\Actions\Action;
use LaraArabDev\Recordkeeper\Models\Audit;
use LaraArabDev\RecordkeeperFilament\RecordkeeperPlugin;

/**
 * Static helpers for formatting audit data in Filament tables and infolists.
 */
final class AuditFormatter
{
    /** @var object{total: int, created: int, updated: int, deleted: int, routes: int, actors: int}|null */
    private static ?object $eventCountsCache = null;

    public static function eventColor(string $event): string
    {
        return match ($event) {
            'created' => 'success',
            'updated' => 'warning',
            'deleted', 'forceDeleted' => 'danger',
            default => str_starts_with($event, 'route.') ? 'info' : 'gray',
        };
    }

    public static function actorLabel(mixed $userId, mixed $userType): string
    {
        if ($userId === null || $userId === 0 || $userId === '') {
            return 'system';
        }

        return class_basename((string) ($userType ?? 'User')).' #'.$userId;
    }

    public static function subjectLabel(mixed $auditableType, mixed $auditableId): string
    {
        return $auditableType
            ? class_basename((string) $auditableType).' #'.$auditableId
            : '—';
    }

    /**
     * Aggregate event counts and distinct actors in a single query (memoized per request).
     *
     * @return object{total: int, created: int, updated: int, deleted: int, routes: int, actors: int}
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
                'COUNT(DISTINCT user_id) as actors',
            ]))
            ->first();

        $zero = (object) ['total' => 0, 'created' => 0, 'updated' => 0, 'deleted' => 0, 'routes' => 0, 'actors' => 0];

        if ($row === null) {
            return self::$eventCountsCache = $zero;
        }

        return self::$eventCountsCache = (object) [
            'total' => (int) $row->total,
            'created' => (int) $row->created,
            'updated' => (int) $row->updated,
            'deleted' => (int) $row->deleted,
            'routes' => (int) $row->routes,
            'actors' => (int) $row->actors,
        ];
    }

    public static function resetEventCountsCache(): void
    {
        self::$eventCountsCache = null;
    }

    public static function revertAction(): Action
    {
        return Action::make('revert')
            ->label(__('filament-recordkeeper::audit.actions.revert'))
            ->icon('heroicon-o-arrow-uturn-left')
            ->color('warning')
            ->requiresConfirmation()
            ->visible(fn (Audit $record) => self::canRevert($record))
            ->action(fn (Audit $record) => $record->rollback());
    }

    /**
     * Checks audit rollbackability, core config, and plugin enablement.
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
