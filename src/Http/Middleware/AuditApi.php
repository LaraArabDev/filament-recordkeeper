<?php

declare(strict_types=1);

namespace LaraArabDev\Recordkeeper\Http\Middleware;

use Illuminate\Http\Request;
use LaraArabDev\Recordkeeper\Resolvers\ApiActorResolver;

final class AuditApi extends BaseAuditMiddleware
{
    protected function guard(): string
    {
        return 'api';
    }

    protected function resolveActor(Request $request): mixed
    {
        return ApiActorResolver::resolve();
    }
}
