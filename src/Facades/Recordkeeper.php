<?php

declare(strict_types=1);

namespace LaraArabDev\Recordkeeper\Facades;

use Illuminate\Support\Facades\Facade;

class Recordkeeper extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \LaraArabDev\Recordkeeper\Recordkeeper::class;
    }
}
