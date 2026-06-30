<?php

declare(strict_types=1);

namespace LaraArabDev\Recordkeeper\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use LaraArabDev\Recordkeeper\Models\Audit;

final class ChangeRecorded
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(
        public readonly Audit $audit,
    ) {}
}
