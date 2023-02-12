<?php

namespace LaravelBridge\Traits;

use LaravelBridge\Interfaces\Payload;

trait Authenticated
{
    public function __construct(Payload $payload)
    {
        parent::__construct($payload);
        $guard = $this->guard_in_use();
        if (! $guard || ! auth()->guard($guard)->user()) {
            $this->redirect('/');
        }
    }
}
