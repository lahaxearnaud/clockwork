<?php
namespace Clockwork\Support\Monolog\Handler;

use Clockwork\Request\Log as ClockworkLog;
use Illuminate\Support\Facades\Event;
use Monolog\Handler\AbstractProcessingHandler;

/**
 * Class ClockworkHandler
 *
 *
 *
 * @package Clockwork\Support\Monolog\Handler
 * @author  LAHAXE Arnaud
 */
class ClockworkHandler extends AbstractProcessingHandler
{
    protected function write(array $record)
    {
        $message = $record['message'];
        if (is_object($message)) {
            if (method_exists($message, '__toString')) {
                $message = (string)$message;
            } else if (method_exists($message, 'toArray')) {
                $message = json_encode($message->toArray());
            } else {
                $message = json_encode((array)$message);
            }
        } else if (is_array($message)) {
            $message = json_encode($message);
        }

        Event::fire(new \Clockwork\Support\JsonPatch\Event('add', 'log/-', [
            'message' => $message,
            'level'   => $record['level'],
            'time'    => microtime(true),
            'stack'   => $this->debugBacktraceDtring()
        ]));
    }

    /**
     * @author LAHAXE Arnaud
     *
     *
     * @return string
     */
    function debugBacktraceString()
    {
        $stack = '';
        $i     = 1;
        $trace = debug_backtrace();
        $trace = array_slice($trace, 4); // remove unwanted rows (clockwork...)
        foreach ($trace as $node) {
            if (!isset($node['file'])) {
                continue;
            }
            $node['file'] = str_replace(base_path(), '', $node['file']);
            $stack .= "#$i " . $node['file'] . "(" . $node['line'] . "): ";
            if (isset($node['class'])) {
                $stack .= $node['class'] . "->";
            }
            $stack .= $node['function'] . "()" . PHP_EOL;
            $i++;
        }

        return $stack;
    }
}
