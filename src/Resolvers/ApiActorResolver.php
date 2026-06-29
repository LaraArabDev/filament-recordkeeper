<?php

declare(strict_types=1);

namespace LaraArabDev\Recordkeeper\Resolvers;

use Illuminate\Support\Facades\Auth;
use OwenIt\Auditing\Contracts\UserResolver;

final class ApiActorResolver implements UserResolver
{
    /** @return mixed */
    public static function resolve(): mixed
    {
        foreach (config('auth.guards', []) as $name => $guard) {
            if ($name === 'web') {
                continue;
            }
            $user = Auth::guard($name)->user();
            if ($user !== null) {
                return $user;
            }
        }

        return Auth::user();
    }
}
