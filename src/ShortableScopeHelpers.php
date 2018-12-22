<?php
namespace Gorankrgovic\LaravelShortable;

use Illuminate\Database\Eloquent\Builder;


trait ShortableScopeHelpers
{
    /**
     * Primary short column of this model.
     *
     * @return string
     */
    public function getSlugKeyName(): string
    {
        if (property_exists($this, 'shortKeyName')) {
            return $this->shortKeyName;
        }
        $config = $this->shortable();
        $name = reset($config);
        $key = key($config);
        // check for short configuration
        if ($key === 0) {
            return $name;
        }
        return $key;
    }
    /**
     * Primary short value of this model.
     *
     * @return string
     */
    public function getShortKey(): string
    {
        return $this->getAttribute($this->getShortKeyName());
    }


    /**
     * Query scope for finding a model by its primary short.
     *
     * @param \Illuminate\Database\Eloquent\Builder $scope
     * @param string $short
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWhereShort(Builder $scope, string $short): Builder
    {
        return $scope->where($this->getShortKeyName(), $short);
    }


    /**
     * Find a model by its primary short.
     *
     * @param string $short
     * @param array $columns
     * @return \Illuminate\Database\Eloquent\Model|\Illuminate\Database\Eloquent\Collection|static[]|static|null
     */
    public static function findByShort(string $short, array $columns = ['*'])
    {
        return static::whereShort($short)->first($columns);
    }


    /**
     * Find a model by its primary short or throw an exception.
     *
     * @param string $short
     * @param array $columns
     * @return \Illuminate\Database\Eloquent\Model|\Illuminate\Database\Eloquent\Collection
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public static function findByShortOrFail(string $short, array $columns = ['*'])
    {
        return static::whereShort($short)->firstOrFail($columns);
    }


}
