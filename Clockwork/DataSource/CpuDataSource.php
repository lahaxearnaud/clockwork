<?php namespace Clockwork\DataSource;

use Clockwork\Request\Request;
use Illuminate\Support\Facades\Event;

class CpuDataSource implements ExtraDataSourceInterface
{

    protected $initialCpuUsage;

    /**
     * EventsDataSource constructor.
     */
    public function __construct()
    {
        $cpu = getrusage();

        $this->initialCpuUsage = $cpu["ru_utime.tv_sec"] * 1e6 + $cpu["ru_utime.tv_usec"];
    }

    /**
     * @return string
     */
    public function getKey()
    {
        return 'cpu';
    }

    /**
     * Adds data to the request and returns it
     */
    public function resolve(Request $request)
    {
        $cpu = getrusage();

        return ($cpu["ru_utime.tv_sec"] * 1e6 + $cpu["ru_utime.tv_usec"]) - $this->initialCpuUsage;
    }
}