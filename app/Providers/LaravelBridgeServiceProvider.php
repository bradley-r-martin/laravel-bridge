<?php

namespace LaravelBridge\Providers;
use Illuminate\Support\ServiceProvider;
class LaravelBridgeServiceProvider extends ServiceProvider{

    public function boot(){
        $this->loadRoutesFrom(dirname(__DIR__, 2).'/routes/web.php');
    }
    public function register(){

    }
}
