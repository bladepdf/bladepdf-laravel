<?php

declare(strict_types=1);

namespace BladePDF\Laravel\Facades;

use Illuminate\Support\Facades\Facade;

class BladePDF extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'bladepdf';
    }
}
