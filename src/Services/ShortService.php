<?php
namespace Gorankrgovic\LaravelShortable\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Class ShortService
 *
 * @package Gorankrgovic\LaravelShortable
 */
class ShortService
{
    /**
     * @var \Illuminate\Database\Eloquent\Model;
     */
    protected $model;

    /**
     * Short the current model.
     *
     * @param \Illuminate\Database\Eloquent\Model $model
     * @param bool $force
     *
     * @return bool
     */
    public function short(Model $model, bool $force = false): bool
    {
        $this->setModel($model);

        $attributes = [];

        foreach ($this->model->shortable() as $attribute => $config) {

            if (is_numeric($attribute)) {
                $attribute = $config;
                $config = $this->getConfiguration();
            } else {
                $config = $this->getConfiguration($config);
            }

            $short = $this->buildShort($attribute, $config, $force);
            $this->model->setAttribute($attribute, $short);
            $attributes[] = $attribute;
        }
        return $this->model->isDirty($attributes);
    }



    /**
     * Get the shortable configuration for the current model,
     * including default values where not specified.
     *
     * @param array $overrides
     *
     * @return array
     */
    public function getConfiguration(array $overrides = []): array
    {
        $defaultConfig = config('shortable', []);
        return array_merge($defaultConfig, $overrides);
    }


    /**
     * Build the short for the given attribute of the current model.
     *
     * @param string $attribute
     * @param array $config
     * @param bool $force
     *
     * @return null|string
     */
    public function buildShort(string $attribute, array $config, bool $force = null)
    {
        $short = $this->model->getAttribute($attribute);

        if ($force || $this->needsShorting($attribute, $config)) {
            $short = $this->generateShort($config);
            $short = $this->makeShortUnique($short, $config, $attribute);
        }

        return $short;
    }

    /**
     * Determines whether the model needs shorting.
     *
     * @param string $attribute
     * @param array $config
     *
     * @return bool
     */
    protected function needsShorting(string $attribute, array $config): bool
    {
        if (
            $config['onUpdate'] === true ||
            empty($this->model->getAttributeValue($attribute))
        ) {
            return true;
        }
        if ($this->model->isDirty($attribute)) {
            return false;
        }
        return (!$this->model->exists);
    }




    /**
     * Generate a short
     *
     * @param array $config
     * @return string
     */
    protected function generateShort(array $config): string
    {

        $length = $config['length'];
        $short = Str::random($length);

        return $short;
    }


    /**
     * Checks if the short should be unique, and makes it so if needed.
     *
     * @param string $short
     * @param array $config
     * @param string $attribute
     *
     * @return string
     * @throws \UnexpectedValueException
     */
    protected function makeShortUnique(string $short, array $config, string $attribute): string
    {
        if (!$config['unique']) {
            return $short;
        }

        // find all models where the slug is like the current one
        $list = $this->getExistingShorts($short, $attribute, $config);

        // if ...
        // 	a) the list is empty, or
        // 	b) our short isn't in the list
        // ... we are okay
        if (
            $list->count() === 0 ||
            $list->contains($short) === false
        ) {
            return $short;
        }

        // if our short is in the list, but
        // 	a) it's for our model
        // ... we are also okay (use the current slug)
        if ($list->has($this->model->getKey())) {
            $currentShort = $list->get($this->model->getKey());
            if (
                $currentShort === $short ||
                strpos($currentShort, $short) === 0
            ) {
                return $currentShort;
            }
        }

        // Generate the suffix - the safest thing to do
        $suffix = $this->generateSuffix($short, $list);

        return $short . '-' . $suffix;
    }


    /**
     * Append the suffix
     *
     * @param string $short
     * @param Collection $list
     * @return string
     */
    protected function generateSuffix(string $short, Collection $list ): string
    {
        $len = strlen($short . '-');

        // If the slug already exists, but belongs to
        // our model, return the current suffix.

        if ($list->search($short) === $this->model->getKey()) {
            $suffix = explode('-', $short);
            return end($suffix);
        }

        $list->transform(function($value, $key) use ($len) {
            return (int) substr($value, $len);
        });

        // find the highest value and return one greater.
        return $list->max() + 1;
    }



    /**
     * Generate another short
     *
     * @param string $short
     * @param array $config
     * @param Collection $list
     * @return string
     */
    protected function generateAnother(string $short, array $config, Collection $list): string
    {
        // If the short already exists, but belongs to
        // our model, return the current.
        if ($list->search($short) === $this->model->getKey()) {
            return $short;
        }

        // find the highest value and return one greater.
        return $this->generateShort($config);
    }


    /**
     * Get all existing shorts that are similar to the given slug.
     *
     * @param string $short
     * @param string $attribute
     * @param array $config
     *
     * @return \Illuminate\Support\Collection
     */
    protected function getExistingShorts(string $short, string $attribute, array $config): Collection
    {

        $includeTrashed = $config['includeTrashed'];

        $query = $this->model->newQuery()
            ->findSimilarShorts($attribute, $short);

        // include trashed models if required
        if ($includeTrashed && $this->usesSoftDeleting()) {
            $query->withTrashed();
        }
        // get the list of all matching slugs
        $results = $query->select([$attribute, $this->model->getQualifiedKeyName()])
            ->get()
            ->toBase();

        // key the results and return
        return $results->pluck($attribute, $this->model->getKeyName());
    }




    /**
     * Generate a unique short
     *
     * @param \Illuminate\Database\Eloquent\Model|string $model
     * @param string $attribute
     * @param string $fromString
     * @param array|null $config
     *
     * @return string
     * @throws \InvalidArgumentException
     * @throws \UnexpectedValueException
     */
    public static function createSlug($model, string $attribute, array $config = null): string
    {
        if (is_string($model)) {
            $model = new $model;
        }

        /** @var static $instance */
        $instance = (new static())->setModel($model);

        if ($config === null) {
            $config = array_get($model->shortable(), $attribute);
            if ($config === null) {
                $modelClass = get_class($model);
                throw new \InvalidArgumentException("Argument 2 passed to ShortService::createShort ['{$attribute}'] is not a valid short attribute for model {$modelClass}.");
            }
        } elseif (!is_array($config)) {
            throw new \UnexpectedValueException('ShortService::createShort expects an array or null as the fourth argument; ' . gettype($config) . ' given.');
        }
        $config = $instance->getConfiguration($config);
        $short = $instance->generateShort($config);
        $short = $instance->makeShortUnique($short, $config, $attribute);
        return $short;
    }

    /**
     * Does this model use softDeleting?
     *
     * @return bool
     */
    protected function usesSoftDeleting(): bool
    {
        return method_exists($this->model, 'bootSoftDeletes');
    }

    /**
     * @param \Illuminate\Database\Eloquent\Model $model
     *
     * @return $this
     */
    public function setModel(Model $model)
    {
        $this->model = $model;
        return $this;
    }




}