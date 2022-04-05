<?php

declare(strict_types=1);

namespace Thiagoprz\CrudTools\Models;

use Illuminate\Support\Facades\Auth;

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
        static::created(function ($model) {
            self::registerActivity($model, self::getAttributesFiltered($model), 'log.created');
        });
        static::updated(function ($model) {
            self::registerActivity($model, self::getAttributesFiltered($model), 'log.updated');
        });
    }

    /**
     * @param $model
     * @param $properties
     * @param $log
     * @return void
     */
    private static function registerActivity($model, $properties, $log)
    {
        $user = Auth::user();
        $class = strtolower(class_basename($model));
        activity()
            ->performedOn($model)
            ->causedBy($user)
            ->withProperties($properties)
            ->log(trans($log, ['model' => trans('entities.' . $class)]));
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
            $attributes = array_filter($attributes, function ($attr, $field) use ($hidden) {
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
