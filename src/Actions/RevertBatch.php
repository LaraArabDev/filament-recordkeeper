<?php

declare(strict_types=1);

namespace LaraArabDev\Recordkeeper\Actions;

use LaraArabDev\Recordkeeper\Support\Rollback;

final class RevertBatch
{
    public function __construct(
        private readonly Rollback $rollback,
    ) {}

    public function handle(string $batchId, bool $dryRun = false): array
    {
        return $this->__invoke($batchId, $dryRun);
    }

    public function __invoke(string $batchId, bool $dryRun = false): array
    {
        return $this->rollback->revertBatch($batchId, $dryRun);
    }
}
