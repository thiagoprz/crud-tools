<?php

namespace Thiagoprz\CrudTools\Models;

/**
 * Trait Logable
 * @package Thiagoprz\EasyCrud\Models
 * @extends \Illuminate\Database\Eloquent\Model
 */
trait Logable
{

    /**
     * The "booting" method of the model.
     * Overrides events for "created", "updated"
     *
     * @return void
     */
    public static function bootLogable()
    {
        $user = \Illuminate\Support\Facades\Auth::user();
        static::created(function($model) use($user) {
            $class = strtolower(class_basename($model));
            activity()
                ->performedOn($model)
                ->causedBy($user)
                ->withProperties(self::getAttributesFiltered($model))
                ->log(trans('log.created', ['model' => trans('entities.' . $class)]));
        });
        static::updated(function($model) use($user) {
            $class = strtolower(class_basename($model));
            activity()
                ->performedOn($model)
                ->causedBy($user)
                ->withProperties(self::getAttributesFiltered($model))
                ->log(trans('log.updated', ['model' => trans('entities.' . $class)]));
        });
    }

    /**
     * @param \Illuminate\Database\Eloquent\Model $model
     */
    private static function getAttributesFiltered($model)
    {
        $attributes = $model->getOriginal();
        if (empty($attributes)) { // may be empty on created() event
            $attributes = $model->getAttributes();
        }
        if (!empty($model->hidden)) {
            $hidden = $model->hidden;
            $attributes = array_filter($attributes, function($attr, $field) use($hidden) {
                return !in_array($field, $hidden);
            }, ARRAY_FILTER_USE_BOTH);
        }
        return $attributes;
    }

    public function setLogAttribute()
    {
        // Empty method
    }

}
