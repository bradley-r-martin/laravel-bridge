<?php

namespace LaravelBridge\Interfaces;

class Payload
{
    protected $controller;
    protected $uuid;
    protected $method = null;

    protected $payload = null;

    protected $type = null;

    protected $state = [];

    protected $props = [];

    public function __construct($payload)
    {
        $this->controller = optional($payload)->props['controller'];
        $this->uuid = optional($payload)->uuid;
        $this->type = optional($payload)->type;
        $this->state = optional($payload)->state;
        $this->props = optional($payload)->props;

        $this->method = optional($payload)->method;
        $this->payload = optional($payload)->payload;

    }

    // return state
    public function state()
    {
        return (array) $this->state;
    }

    // return props
    public function props()
    {
        return (array) $this->props;
    }

    public function type()
    {
        return $this->type;
    }

    public function method()
    {
        return $this->method;
    }

    public function payload()
    {
        return $this->payload;
    }

    public function uuid()
    {
        return $this->uuid;
    }

    // return controller;
    public function controller()
    {
      
        if (class_exists($this->controller)) {
            $controller_name = $this->controller;

            return new $controller_name($this);
        }

        return null;
    }
}
