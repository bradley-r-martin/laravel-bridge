<?php

namespace LaravelBridge\Traits;

use LaravelBridge\Interfaces\Payload;

trait Authenticated
{
    public function __construct(Payload $payload)
    {
        parent::__construct($payload);
        if (! auth()->user()) {
            $this->redirect('/');
        }
    }
}
