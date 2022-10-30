<?php

namespace LaravelBridge\Http\Controllers\Bridge;

use LaravelBridge\Interfaces\Payload;
use LaravelBridge\Interfaces\Thread;

class Bridge
{
    protected $_debug = false;

    protected $_payloads = [];

    protected $_redirect_to = null;

    public function __construct()
    {
        $bundle = request()->json()->all();

        $this->_debug = (request()->header('x-debug') === 'true');
        $payload = ($this->_debug ? $bundle : $this->decode($bundle));

        $this->_payloads = collect($payload)->map(function ($payload) {
            return new Payload((object) $payload);
        });
    }

    public function decode($bundle)
    {
        return is_string($bundle) ? json_decode(base64_decode($bundle)) : null;
    }

    public function encode($bundle)
    {
        return base64_encode(json_encode($bundle));
    }

    public function respond()
    {
        // Convert payloads into threads
        $threads = $this->_payloads->map(function ($payload) {
            return new Thread($payload);
        });

        // Run primary controllers event
        $threads->filter(function ($thread) {
            return $thread->payload()->type() === 'CALL';
        })->map(function ($thread) {
            $method = $thread->payload()->method();
            if ($thread->controller() && method_exists($thread->controller(), $method)) {
                $thread->execute(function () use ($thread, $method) {
                    $thread->output = $thread->controller()->$method($thread->payload()->payload());
                });
            }
        });

        // run all controller MOUNT
        $threads->filter(function ($thread) {
            return $thread->payload()->type() === 'MOUNT';
        })->map(function ($thread) {
            optional($thread)->execute(function () use ($thread) {
                optional($thread->controller())->onMount();
            });
        });

        // Run all controllers onSync
        $threads->map(function ($thread) {
            if ($thread->controller() && method_exists($thread->controller(), 'onSync')) {
                $thread->execute(function () use ($thread) {
                    optional($thread->controller())->onSync();
                });
            }
        });

        $states = $threads->map(function ($thread) {
            return $thread->response();
        });

        $redirect = $states->filter(function ($state) {
            return (bool) $state['redirect'];
        })->map(function ($state) {
            return $state['redirect'];
        })->first();

        return array_merge([
            'payload' => $this->_debug ? $states : $this->encode($states),
        ], ($redirect ? ['redirect' => $redirect] : []));
    }
}
