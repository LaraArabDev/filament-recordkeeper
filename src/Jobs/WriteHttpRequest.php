<?php

declare(strict_types=1);

namespace LaraArabDev\Recordkeeper\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use LaraArabDev\Recordkeeper\Models\AuditHttpRequest;

final class WriteHttpRequest implements ShouldQueue
{
    use InteractsWithQueue, Queueable;

    public function __construct(private readonly array $data) {}

    public function handle(): void
    {
        AuditHttpRequest::create($this->data);
    }
}
