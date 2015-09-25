<?php namespace Clockwork\DataSource;

use Clockwork\DataSource\ExtraDataSourceInterface;
use Clockwork\Request\Request;
use Illuminate\Support\Facades\Event;

class MemoryDataSource implements ExtraDataSourceInterface
{

    protected $points;

    /**
     * EventsDataSource constructor.
     */
    public function __construct()
    {
        $this->points = [];

        Event::listen('*', function () {
            $this->points [] = [
                'memory' => memory_get_usage(true),
                'time'   => microtime(true)
            ];
        });
    }

    /**
     * @return string
     */
    public function getKey()
    {
        return 'memory';
    }

    /**
     * Adds data to the request and returns it
     */
    public function resolve(Request $request)
    {

        return $this->points;
    }
}