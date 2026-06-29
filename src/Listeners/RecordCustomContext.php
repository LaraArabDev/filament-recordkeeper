<?php

declare(strict_types=1);

namespace LaraArabDev\Recordkeeper\Listeners;

use LaraArabDev\Recordkeeper\Events\ChangeRecorded;

final class RecordCustomContext
{
    /**
     * @param  ChangeRecorded  $event
     * @return void
     */
    public function handle(ChangeRecorded $event): void
    {
    }
}
