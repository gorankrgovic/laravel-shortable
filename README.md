# Laravel Eloquent Shortable

Easy creation of short random unique ID's like Youtube ones (i.e. watch?v=e3H73n7U) for your Eloquent models in Laravel/Lumen.

## Installation

Install the package via Composer:

```bash 
composer require gorankrgovic/laravel-shortable
```

The package will automatically register its service provider.

Optionally, publish the configuration file if you want to change any defaults:

```bash
php artisan vendor:publish --provider="Gorankrgovic\LaravelShortable\ServiceProvider"
```

## Updating your Eloquent Models

Your models should use the Shortable trait, which has an abstract method shortable() that you need to define. This is where any model-specific configuration is set (see Configuration below for details):

```php

use Gorankrgovic\LaravelShortable\Shortable;

class Video extends Model
{
    use Shortable;

    /**
     * Return the shortable configuration array for this model.
     *
     * @return array
     */
    public function shortable()
    {
        return [
            'short_url'
        ];
    }
}

```


If you want more than one field, you can just add more to array.

Of course, your model and database will need a column in which to store the short ID. You will need to add the column manually via your own migration.

That's it ... your model is now "shortable"!

## Usage

Saving a model is easy:

```php

$video = new Video([
    'whateve' => 'My Awesome Video',
]);

$video->save();

```

And so is retrieving the shortable:


```php
$video->short_url;
```

Or whatever you have called it.

## The ShortService Class

All the logic to generate slugs is handled by the `\Gorankrgovic\LaravelShortable\Services\ShortService` class.

Generally, you don't need to access this class directly, although there is one static method that can be used to generate a short without actually creating or saving an associated model.

```php
use Gorankrgovic\LaravelShortable\Services\ShortService;

$short = ShortService::createShort(Post::class, 'column_name');
```

This would be useful for testing the package.

You can also pass an optional array of configuration values as the fourth argument.

## Events

Package will fire two Eloquent model events: "shorting" and "shorted".

You can hook into either of these events just like any other Eloquent model event:

```php

Post::registerModelEvent('shorting', function($post) {
    if ($post->someCondition()) {
        // the model won't be shorted
        return false;
    }
});

Post::registerModelEvent('shorted', function($post) {
    Log::info('Post shorted: ' . $post->getShort());
});

```


## Additional trait

Adding the optional ShortableScopeHelpers trait to your model allows you to work with models and their id's. For example:


```php
$post = Post::whereShort($shortString)->get();

$post = Post::findByShort($shortString);

$post = Post::findByShortOrFail($shortString);

```



Because models can have more than one short id, this requires a bit more configuration.