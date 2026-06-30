<?php

declare(strict_types=1);

namespace LaraArabDev\Recordkeeper\Contracts;

use LaraArabDev\Recordkeeper\Models\Audit;

interface Rollbacker
{
    public function revert(Audit $audit, bool $dryRun = false): mixed;

    public function revertBatch(string $batchId, bool $dryRun = false): array;
}
