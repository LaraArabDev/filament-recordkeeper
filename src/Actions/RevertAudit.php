<?php

declare(strict_types=1);

namespace LaraArabDev\Recordkeeper\Actions;

use LaraArabDev\Recordkeeper\Models\Audit;
use LaraArabDev\Recordkeeper\Support\Rollback;

final class RevertAudit
{
    public function __construct(
        private readonly Rollback $rollback,
    ) {}

    public function handle(Audit $audit, bool $dryRun = false): mixed
    {
        return $this->__invoke($audit, $dryRun);
    }

    public function __invoke(Audit $audit, bool $dryRun = false): mixed
    {
        return $this->rollback->revert($audit, $dryRun);
    }
}
