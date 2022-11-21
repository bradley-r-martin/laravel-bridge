<?php

namespace LaravelBridge\Casts;

use Illuminate\Contracts\Database\Eloquent\Castable;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use LaravelBridge\Interfaces\File;

class FileCast implements Castable
{
    public static function castUsing(array $arguments)
    {
        return new class implements CastsAttributes
        {
            public function get($model, $key, $value, $attributes)
            {
                if ($value) {
                    return new File(json_decode($value));
                }

                return null;
            }

            public function set($model, $key, $value, $attributes)
            {
                if ($value && $value->key) {
                    return json_encode($value->toArray());
                }

                return null;
            }
        };
    }
}
