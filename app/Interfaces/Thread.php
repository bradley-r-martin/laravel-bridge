<?php

namespace LaravelBridge\Interfaces;

class Thread
{
    protected $payload;

    protected $controller;

    public $output;

    protected $response = [];

    protected $exceptions = [];

    protected $redirect = null;

    public function __construct($payload)
    {
        $this->payload = $payload;
        try {
            $this->controller = $payload->controller();
        } catch(\Illuminate\Http\Exceptions\HttpResponseException $e) {
            $this->redirect = $e->getResponse()->getTargetUrl();
        }
    }

    public function execute($func)
    {
        try {
            if ($func) {
               return $func();
            }
        } catch(\Illuminate\Validation\ValidationException $e) {
            $this->exceptions = array_merge($this->_exceptions, $e->validator->messages()->getMessages());
        } catch(\Illuminate\Http\Exceptions\HttpResponseException $e) {
            $this->redirect = $e->getResponse()->getTargetUrl();
        }
    }

    public function payload()
    {
        return $this->payload;
    }

    public function controller()
    {
        return $this->controller;
    }

    public function exceptions()
    {
        return $this->exceptions;
    }

    public function output()
    {
        return $this->output;
    }

    public function response()
    {
        return [
            'type' => $this->payload()->type(),
            'uuid' => $this->payload()->uuid(),
            'props' => $this->payload()->props(),
            'state' => optional($this->controller())->state(),
            'response' => $this->output(),
            'exceptions' => $this->exceptions(),
            'redirect' => $this->redirect,
        ];
    }
}
