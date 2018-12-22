<?php
namespace Gorankrgovic\LaravelShortable;


use Gorankrgovic\LaravelShortable\Services\ShortService;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\Eloquent\Model;


class ShortableObserver
{

    private $shortService;

    /**
     * @var \Illuminate\Contracts\Events\Dispatcher
     */
    private $events;

    /**
     * ShortableObserver constructor.
     * @param ShortService $shortService
     * @param Dispatcher $events
     */
    public function __construct(ShortService $shortService, Dispatcher $events)
    {
        $this->shortService = $shortService;
        $this->events = $events;
    }

    /**
     * @param \Illuminate\Database\Eloquent\Model $model
     * @return boolean|null
     */
    public function saving(Model $model)
    {
        return $this->generateShort($model, 'saving');
    }

    /**
     * @param \Illuminate\Database\Eloquent\Model $model
     * @param string $event
     * @return boolean|void
     */
    protected function generateShort(Model $model, string $event)
    {
        // If the "shorting" event returns a value, abort
        if ($this->fireShortingEvent($model, $event) !== null) {
            return;
        }
        $wasShorted = $this->shortService->short($model);
        $this->fireShortedEvent($model, $wasShorted);
    }

    /**
     * Fire the namespaced validating event.
     *
     * @param  \Illuminate\Database\Eloquent\Model $model
     * @param  string $event
     * @return mixed
     */
    protected function fireShortingEvent(Model $model, string $event)
    {
        return $this->events->until('eloquent.shorting: ' . get_class($model), [$model, $event]);
    }


    /**
     * Fire the namespaced post-validation event.
     *
     * @param  \Illuminate\Database\Eloquent\Model $model
     * @param  string $status
     */
    protected function fireShortedEvent(Model $model, string $status)
    {
        $this->events->dispatch('eloquent.shorted: ' . get_class($model), [$model, $status]);
    }



}