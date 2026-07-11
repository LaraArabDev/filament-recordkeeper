<?php

declare(strict_types=1);

namespace LaraArabDev\RecordkeeperFilament\Support;

use LaraArabDev\Recordkeeper\Models\Audit;

/** Shared formatting helpers used across the Filament audit UI. */
final class AuditFormatter
{
    /** Map an audit event name to a Filament badge color. */
    public static function eventColor(string $event): string
    {
        return match (true) {
            $event === 'created' => 'success',
            $event === 'updated' => 'warning',
            in_array($event, ['deleted', 'forceDeleted'], true) => 'danger',
            str_starts_with($event, 'route.') => 'info',
            default => 'gray',
        };
    }

    /** Format an audit's actor as a human-readable label. */
    public static function actorLabel(mixed $userId, mixed $userType): string
    {
        return $userId
            ? class_basename((string) ($userType ?? 'User')).' #'.$userId
            : 'system';
    }

    /** Format an audit's subject (auditable) as a human-readable label. */
    public static function subjectLabel(mixed $auditableType, mixed $auditableId): string
    {
        return $auditableType
            ? class_basename((string) $auditableType).' #'.$auditableId
            : '—';
    }

    /** Determine whether the revert action should be visible for an audit record. */
    public static function canRevert(Audit $audit): bool
    {
        return $audit->isRollbackable()
            && config('recordkeeper.filament.rollback_enabled', false)
            && config('recordkeeper.rollback.enabled', true);
    }
}
