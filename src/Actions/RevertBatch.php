<?php

declare(strict_types=1);

namespace LaraArabDev\Recordkeeper\Actions;

use LaraArabDev\Recordkeeper\Support\Rollback;

final class RevertBatch
{
    public function __construct(
        private readonly Rollback $rollback,
    ) {}

    /**
     * @param  string  $batchId
     * @param  bool    $dryRun
     * @return array
     */
    public function handle(string $batchId, bool $dryRun = false): array
    {
        return $this->__invoke($batchId, $dryRun);
    }

    /**
     * @param  string  $batchId
     * @param  bool    $dryRun
     * @return array
     */
    public function __invoke(string $batchId, bool $dryRun = false): array
    {
        return $this->rollback->revertBatch($batchId, $dryRun);
    }
}
