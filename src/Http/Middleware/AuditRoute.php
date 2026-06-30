<?php

declare(strict_types=1);

namespace LaraArabDev\Recordkeeper\Http\Middleware;

final class AuditRoute extends BaseAuditMiddleware
{
    protected function guard(): string
    {
        return 'web';
    }
}
