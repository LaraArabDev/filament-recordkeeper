<?php

declare(strict_types=1);

namespace LaraArabDev\Recordkeeper\Contracts;

use LaraArabDev\Recordkeeper\Models\Audit;

interface Rollbacker
{
    /**
     * @param  Audit  $audit
     * @param  bool   $dryRun
     * @return mixed
     */
    public function revert(Audit $audit, bool $dryRun = false): mixed;

    /**
     * @param  string  $batchId
     * @param  bool    $dryRun
     * @return array
     */
    public function revertBatch(string $batchId, bool $dryRun = false): array;
}
