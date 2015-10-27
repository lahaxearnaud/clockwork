<?php namespace Clockwork\DataSource;

use Clockwork\Facade\Clockwork;
use Clockwork\Request\Request;
use Illuminate\Support\Facades\Event;

class EventsDataSource implements ExtraDataSourceInterface
{

    protected $events;

    /**
     * EventsDataSource constructor.
     */
    public function __construct()
    {
        $this->events = [];

        Event::listen('*', function ($param) {
            /** @todo fix that shit */
            if(!class_exists('events')) {
                return;
            }
            
            $this->events [] = [
                'name'  => Event::firing(),
                'param' => json_encode($param),
                'time'  => microtime(true)
            ];

            $currentTime = microtime(true);
            Clockwork::addEvent(uniqid('event_'), Event::firing(), $currentTime, $currentTime);
        });
    }

    /**
     * @return string
     */
    public function getKey()
    {
        return 'events';
    }

    /**
     * Adds data to the request and returns it
     */
    public function resolve(Request $request)
    {

        return $this->events;
    }
}
