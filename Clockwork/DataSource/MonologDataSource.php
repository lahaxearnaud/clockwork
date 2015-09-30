<?php
namespace Clockwork\DataSource;

use Clockwork\Support\Monolog\Handler\ClockworkHandler;
use Monolog\Logger as Monolog;

/**
 * Data source for Monolog, provides application log
 */
class MonologDataSource extends DataSource
{

    /**
     * Create a new data source, takes Laravel application instance as an argument
     */
    public function __construct(Monolog $monolog)
    {
        $monolog->pushHandler(new ClockworkHandler());
    }
}
