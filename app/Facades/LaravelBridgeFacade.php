<?php

namespace LaravelBridge;

use Illuminate\Support\Facades\Facade;

class LaravelBridgeFacade extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'laravel-bridge';
    }
}
