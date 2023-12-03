<?php

namespace JoshEmbling\Laragenie\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \JoshEmbling\Laragenie\Laragenie
 */
class Laragenie extends Facade
{
    protected static function getFacadeAccessor()
    {
        return \JoshEmbling\Laragenie\Laragenie::class;
    }
}
