<?php

declare(strict_types=1);

namespace LaraArabDev\Recordkeeper\Http\Middleware;

use Illuminate\Http\Request;
use LaraArabDev\Recordkeeper\Resolvers\ApiActorResolver;

final class AuditApi extends BaseAuditMiddleware
{
    /** @return string */
    protected function guard(): string
    {
        return 'api';
    }

    /**
     * @param  Request  $request
     * @return mixed
     */
    protected function resolveActor(Request $request): mixed
    {
        return ApiActorResolver::resolve();
    }
}
