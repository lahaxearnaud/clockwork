<?php
namespace Clockwork\Request;

use Illuminate\Support\Facades\Event;

/**
 * Class Timeline
 *
 *
 *
 * @package Clockwork\Request
 * @author  LAHAXE Arnaud
 */
class Timeline
{

    /**
     * Timeline data
     */
    public $data = array();

    /**
     * @author LAHAXE Arnaud
     *
     * @param       $name
     * @param       $description
     * @param       $start_time
     * @param       $end_time
     * @param array $data
     *
     */
    public function addEvent($name, $description, $start_time, $end_time, array $data = array())
    {
        Event::fire(new \Clockwork\Support\JsonPatch\Event('add', 'timelineData/-', [
            'start'       => $start_time,
            'end'         => $end_time,
            'duration'    => null,
            'description' => $description,
            'data'        => $data,
        ]));
    }

    /**
     * @author LAHAXE Arnaud
     *
     * @param       $name
     * @param       $description
     * @param null  $time
     * @param array $data
     *
     */
    public function startEvent($name, $description, $time = null, array $data = array())
    {
        $this->data[$name] = array(
            'start'       => $time ? $time : microtime(true),
            'end'         => null,
            'duration'    => null,
            'description' => $description,
            'data'        => $data,
        );
    }

    /**
     * @author LAHAXE Arnaud
     *
     * @param $name
     *
     * @return bool
     */
    public function endEvent($name)
    {
        if (!isset($this->data[$name]))
            return false;

        $this->data[$name]['end'] = microtime(true);

        if (is_numeric($this->data[$name]['start'])) {
            $this->data[$name]['duration'] = ($this->data[$name]['end'] - $this->data[$name]['start']) * 1000;
        }

        Event::fire(new \Clockwork\Support\JsonPatch\Event('add', 'timelineData/-', $this->data[$name]));

        unset($this->data[$name]);

        return true;
    }
}
