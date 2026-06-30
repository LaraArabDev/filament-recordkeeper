<?php

declare(strict_types=1);

namespace LaraArabDev\Recordkeeper\Actions;

use Illuminate\Support\Carbon;
use LaraArabDev\Recordkeeper\Models\Audit;

final class PruneAudits
{
    public function handle(int $days, bool $dryRun = false): int
    {
        return $this->__invoke($days, $dryRun);
    }

    public function __invoke(int $days, bool $dryRun = false): int
    {
        $cutoff = Carbon::now()->subDays($days);
        $query = Audit::where('created_at', '<', $cutoff);

        if ($dryRun) {
            return $query->count();
        }

        $deleted = 0;
        $query->chunkById(200, function ($audits) use (&$deleted): void {
            $ids = $audits->pluck('id')->all();
            $deleted += count($ids);
            Audit::whereIn('id', $ids)->delete();
        });

        return $deleted;
    }
}
