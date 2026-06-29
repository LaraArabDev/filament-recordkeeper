<?php

declare(strict_types=1);

namespace LaraArabDev\Recordkeeper\Support;

use Illuminate\Support\Facades\DB;
use LaraArabDev\Recordkeeper\Contracts\Rollbacker;
use LaraArabDev\Recordkeeper\Modifiers\EncryptAttribute;
use LaraArabDev\Recordkeeper\Models\Audit;

final class Rollback implements Rollbacker
{
    /**
     * @param  Audit  $audit
     * @param  bool   $dryRun
     * @return mixed
     */
    public function revert(Audit $audit, bool $dryRun = false): mixed
    {
        if (! config('recordkeeper.rollback.enabled', true)) {
            throw new \RuntimeException('Rollback is disabled in recordkeeper config.');
        }

        return match (true) {
            $audit->event === 'created'                                => $this->undoCreate($audit, $dryRun),
            in_array($audit->event, ['deleted', 'forceDeleted'], true) => $this->restore($audit, $dryRun),
            default                                                    => $this->undoUpdate($audit, $dryRun),
        };
    }

    /**
     * @param  string  $batchId
     * @param  bool    $dryRun
     * @return array
     */
    public function revertBatch(string $batchId, bool $dryRun = false): array
    {
        $audits = Audit::where('batch_id', $batchId)
            ->whereIn('event', ['created', 'updated', 'deleted', 'restored'])
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->get();

        if ($dryRun) {
            return $audits->map(fn (Audit $a) => $this->revert($a, true))->all();
        }

        return DB::transaction(function () use ($audits): array {
            return $audits->map(fn (Audit $a) => $this->revert($a, false))->all();
        });
    }

    /**
     * @param  Audit  $audit
     * @param  bool   $dryRun
     * @return mixed
     */
    private function undoCreate(Audit $audit, bool $dryRun): mixed
    {
        if ($dryRun) {
            return [
                'action'         => 'delete',
                'auditable_type' => $audit->auditable_type,
                'auditable_id'   => $audit->auditable_id,
            ];
        }

        $model = $audit->auditable;

        if ($model === null) {
            return null;
        }

        return $model->forceDelete();
    }

    /**
     * @param  Audit  $audit
     * @param  bool   $dryRun
     * @return mixed
     */
    private function restore(Audit $audit, bool $dryRun): mixed
    {
        if (! config('recordkeeper.rollback.restore_deleted', true)) {
            throw new \RuntimeException('restore_deleted is disabled in recordkeeper config.');
        }

        $oldValues = $this->decryptValues((array) ($audit->old_values ?? []));

        if ($dryRun) {
            return ['action' => 'restore', 'attributes' => $oldValues];
        }

        $modelClass = $audit->auditable_type;

        $usesSoftDeletes = in_array(
            \Illuminate\Database\Eloquent\SoftDeletes::class,
            class_uses_recursive($modelClass),
        );

        if ($usesSoftDeletes) {
            $existing = $modelClass::withTrashed()->find($audit->auditable_id);
            if ($existing !== null) {
                $existing->restore();

                return $existing;
            }
        }

        return $modelClass::create(array_merge($oldValues, ['id' => $audit->auditable_id]));
    }

    /**
     * @param  Audit  $audit
     * @param  bool   $dryRun
     * @return mixed
     */
    private function undoUpdate(Audit $audit, bool $dryRun): mixed
    {
        if ($dryRun) {
            return ['action' => 'update', 'attributes' => $audit->getModified()];
        }

        $model = $audit->auditable;

        if ($model === null) {
            return null;
        }

        $oldValues = $this->decryptValues((array) ($audit->old_values ?? []));

        $model->disableAuditing();

        try {
            $model->fill($oldValues)->save();
        } finally {
            $model->enableAuditing();
        }

        $audit->delete();

        return $model;
    }

    /**
     * @param  array  $values
     * @return array
     */
    private function decryptValues(array $values): array
    {
        return array_map(function (mixed $value): mixed {
            if (is_string($value) && EncryptAttribute::isEncrypted($value)) {
                return EncryptAttribute::decrypt($value);
            }

            return $value;
        }, $values);
    }
}
