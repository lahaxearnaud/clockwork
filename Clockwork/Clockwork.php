<?php
namespace Clockwork;

use Clockwork\DataSource\DataSourceInterface;
use Clockwork\DataSource\ExtraDataSourceInterface;
use Clockwork\Request\Request;
use Clockwork\Request\Timeline;
use Clockwork\Storage\StorageInterface;

/**
 * Main Clockwork class
 */
class Clockwork
{

    /**
     * Clockwork version (used for chrome extension)
     */
    const VERSION = '1.9';

    /**
     * Array of data sources, these objects provide data to be stored in a request object
     */
    protected $dataSources = array();

    /**
     * Request object, data structure which stores data about current application request
     */
    protected $request;

    /**
     * Storage object, provides implementation for storing and retrieving request objects
     */
    protected $storage;

    /**
     * Request\Log instance, data structure which stores data for the log view
     */
    protected $log;

    /**
     * Request\Timeline instance, data structure which stores data for the timeline view
     */
    protected $timeline;

    /**
     * Create a new Clockwork instance with default request object
     */
    public function __construct()
    {
        $this->request  = new Request();
        $this->timeline = new Timeline();
    }

    /**
     * Add a new data source
     */
    public function addDataSource(DataSourceInterface $dataSource)
    {
        $this->dataSources[] = $dataSource;

        return $this;
    }

    /**
     * Return array of all added data sources
     */
    public function getDataSources()
    {
        return $this->dataSources;
    }

    /**
     * Return the request object
     */
    public function getRequest()
    {
        return $this->request;
    }

    /**
     * Set a custom request object
     */
    public function setRequest(Request $request)
    {
        $this->request = $request;

        return $this;
    }

    /**
     * Add data from all data sources to request
     */
    public function resolveRequest()
    {
        foreach ($this->dataSources as $dataSource) {
            if ($dataSource instanceof ExtraDataSourceInterface) {
                $this->request->extra[$dataSource->getKey()] = $dataSource->resolve($this->request);
            } else {
                $dataSource->resolve($this->request);
            }
        }

        return $this;
    }

    /**
     * Store request via storage object
     */
    public function storeRequest()
    {
        return $this->storage->store($this->request);
    }

    /**
     * Return the storage object
     */
    public function getStorage()
    {
        return $this->storage;
    }

    /**
     * Set a custom storage object
     */
    public function setStorage(StorageInterface $storage)
    {
        $this->storage = $storage;

        return $this;
    }

    /**
     * Return the timeline instance
     */
    public function getTimeline()
    {
        return $this->timeline;
    }

    /**
     * Set a custom timeline instance
     */
    public function setTimeline(Timeline $timeline)
    {
        $this->timeline = $timeline;
    }

    /**
     * Shortcut methods for the current timeline instance
     */

    public function startEvent($name, $description, $time = null, $data = [])
    {
        $this->getTimeline()->startEvent($name, $description, $time, $data);
    }

    public function endEvent($name)
    {
        $this->getTimeline()->endEvent($name);
    }

    public function addEvent($name, $description, $timeStart, $timeEnd, $data = [])
    {
        $this->getTimeline()->addEvent($name, $description, $timeStart, $timeEnd, $data);
    }
}
