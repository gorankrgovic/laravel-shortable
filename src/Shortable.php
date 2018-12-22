<?php
namespace Gorankrgovic\LaravelShortable;

use Gorankrgovic\LaravelShortable\Services\ShortService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Trait Shortable
 *
 * @package Gorankrgovic\LaravelShortable
 */
trait Shortable
{
    /**
     * Hook into the Eloquent model events to create or
     * update the short as required.
     */
    public static function bootShortable()
    {
        static::observe(app(ShortableObserver::class));
    }


    /**
     * Register a shorting model event with the dispatcher.
     *
     * @param \Closure|string $callback
     */
    public static function shorting($callback)
    {
        static::registerModelEvent('shorting', $callback);
    }


    /**
     * Register a shorted model event with the dispatcher.
     *
     * @param \Closure|string $callback
     */
    public static function shorted($callback)
    {
        static::registerModelEvent('shorted', $callback);
    }

    /**
     * Clone the model into a new, non-existing instance.
     *
     * @param  array|null $except
     * @return \Illuminate\Database\Eloquent\Model
     */
    public function replicate(array $except = null)
    {
        $instance = parent::replicate($except);
        (new ShortService())->short($instance, true);
        return $instance;
    }


    /**
     * Query scope for finding shorts, used to determine uniqueness.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $attribute
     * @param string $short
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeFindSimilarShorts(Builder $query, string $attribute, string $short): Builder
    {
        return $query->where(function(Builder $q) use ($attribute, $short) {
                $q->where($attribute, '=', $short)
                    ->orWhere($attribute, 'LIKE', $short . '-' . '%');
        });
    }

    /**
     * Return the shortable configuration array for this model.
     *
     * @return array
     */
    abstract public function shortable(): array;

}