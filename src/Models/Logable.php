<?php

namespace Thiagoprz\CrudTools\Models;

/**
 * Trait Logable
 * @package Thiagoprz\EasyCrud\Models
 */
trait Logable
{

    /**
     * The "booting" method of the model.
     * Overrides events for "created", "updated"
     *
     * @return void
     */
    protected static function boot()
    {
        parent::boot();
        $user = \Illuminate\Support\Facades\Auth::user();
        static::created(function($model) use($user) {
            $class = strtolower(class_basename($model));
            activity()
                ->performedOn($model)
                ->causedBy($user)
                ->withProperties($model->getOriginal())
                ->log(trans('log.created', ['model' => trans('entities.' . $class)]));
        });
        static::updated(function($model) use($user) {
            $class = strtolower(class_basename($model));
            activity()
                ->performedOn($model)
                ->causedBy($user)
                ->withProperties($model->getOriginal())
                ->log(trans('log.updated', ['model' => trans('entities.' . $class)]));
        });
    }

}