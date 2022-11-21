<?php

namespace LaravelBridge\Interfaces;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Validator;

class Controller
{
    protected Payload $_payload;

    public function __construct(Payload $payload)
    {
        $this->_payload = $payload;
        $this->hydrate();
    }

    // Return component props
    public function props()
    {
        return $this->_payload->props();
    }

    // Return component state
    public function state()
    {
        return $this->dehydrate();
    }

    // validate state
    public function validate()
    {
        $validator = Validator::make($this->state(), optional($this)->rules ?? [])->validate();
    }

    public function redirect($to = '')
    {
        throw new \Illuminate\Http\Exceptions\HttpResponseException(redirect($to));
    }

    public function authorise($action, $model = null)
    {
        if (auth()->user()->cannot($action, $model)) {
            dd('unauthorised');
            $this->redirect('/');
        }
    }

    // Hydrate the payload state into the controller
    protected function hydrate()
    {
        $state = $this->_payload->state();
        foreach ($state as $key => $value) {
            $type = null;
            try {
                $type = optional((new \ReflectionProperty($this, $key))->getType())->getName();
            } catch(\Exception $e) {
            }
            if ($type) {
                $model_key = optional(new $type)->getKeyName();
                if (optional($value)[$model_key]) {
                    $model = (new $type)->find(optional($value)[$model_key]);
                    if ($model && ! is_null($value)) {
                        $this->hydrate_model_attributes($model, (array) $value);
                        $this->{$key} = $model;
                    } else {
                        $model = (new $type);
                        $this->hydrate_model_attributes($model, (array) $value);
                        $this->{$key} = $model;
                    }
                } else {
                    $model = (new $type);
                    $this->hydrate_model_attributes($model, (array) $value);
                    $this->{$key} = $model;
                }
            } else {
                $this->{$key} = $value;
            }
        }
    }

    protected function hydrate_model_attributes($model, $attributes)
    {
        $casts = $model->getCasts();
        $fillable = $model->getFillable();

        collect($attributes)->map(function ($value, $key) use ($model, $casts, $fillable) {
            if (in_array($key, $fillable)) {
                if (optional($casts)[$key] && class_exists(optional($casts)[$key])) {
                    $castable = optional($casts)[$key];
                    $castableClass = (new $castable)->castUsing([]);
                    $model->setAttribute($key, $castableClass->get($model, $key, json_encode((array) $value), []));
                } else {
                    $model->setAttribute($key, $value);
                }
            }
        });

        return $model;
    }

    // dehydate the controller state
    protected function dehydrate()
    {
        $protected = ['_payload', 'rules'];
        $variables = get_object_vars($this);
        $variables = collect($variables)->map(function ($variable, $property) {
            if (is_string($variable) || is_bool($variable) || is_int($variable) || is_float($variable)) {
                return $variable;
            } elseif (is_object($variable)) {
                $type = null;
                try {
                    $type = optional((new \ReflectionProperty($this, $property))->getType())->getName();
                } catch(\Exception $e) {
                }

                if ($type === Carbon::class) {
                    return $variable;
                }

                $object = collect($variable)->toArray();

                return count($object) > 0 ? $object : null;
            } elseif (is_array($variable)) {
                return $variable;
            }
        })->except($protected)->toArray();

        return $variables;
    }

    public function onMount()
    {
    }

    public function onSync()
    {
    }
}
