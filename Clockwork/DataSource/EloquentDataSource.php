<?php
namespace Clockwork\DataSource;

use Clockwork\Facade\Clockwork;
use Clockwork\Request\Request;
use Illuminate\Database\DatabaseManager;
use Illuminate\Events\Dispatcher as EventDispatcher;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;

/**
 * Data source for Eloquent (Laravel 4 ORM), provides database queries
 */
class EloquentDataSource extends DataSource implements LiveDataSourceInterface
{

    /**
     * Database manager
     */
    protected $databaseManager;

    /**
     * Internal array where queries are stored
     *
     * @var array
     */
    protected $queries = array();

    /**
     * Create a new data source instance, takes a database manager and an event dispatcher as arguments
     */
    public function __construct(DatabaseManager $databaseManager, EventDispatcher $eventDispatcher)
    {
        $this->databaseManager = $databaseManager;
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * Start listening to eloquent queries
     */
    public function listenToEvents()
    {
        $this->eventDispatcher->listen('illuminate.query', array($this, 'registerQuery'));
    }

    /**
     * Log the query into the internal store
     *
     * @return array
     */
    public function registerQuery($query, $bindings, $time, $connection)
    {
        $currentTime = microtime(true);
        $explainResults = [];
        if (preg_match('/^(SELECT) /i', $query)) {
            $pdo = DB::connection($connection)->getPdo();
            $statement = $pdo->prepare('EXPLAIN ' . $query);
            $statement->execute($bindings);
            $explainResults = $statement->fetchAll(\PDO::FETCH_CLASS);

            foreach ($explainResults as $key => $value) {
                $explainResults[$key] = (array)$value;
            }
        }

        Event::fire(new \Clockwork\Support\JsonPatch\Event('add', 'extra/events/-', [
            'query'      => $query,
            'bindings'   => $bindings,
            'time'       => $time,
            'connection' => $connection,
            'explain'    => $explainResults
        ]));

        Clockwork::addEvent(uniqid('query_'), 'Sql query', $currentTime - ($time / 1000), $currentTime, [
            'query'     => $query,
            'bindings'  => $bindings
        ]);
    }

    /**
     * Adds ran database queries to the request
     */
    public function resolve(Request $request)
    {
        $request->databaseQueries = array_merge($request->databaseQueries, $this->getDatabaseQueries());

        return $request;
    }

    /**
     * Takes a query, an array of bindings and the connection as arguments, returns runnable query with upper-cased
     * keywords
     */
    protected function createRunnableQuery($query, $bindings, $connection)
    {
        # add bindings to query
        $bindings = $this->databaseManager->connection($connection)->prepareBindings($bindings);

        foreach ($bindings as $binding) {
            $binding = $this->databaseManager->connection($connection)->getPdo()->quote($binding);

            $query = preg_replace('/\?/', $binding, $query, 1);
        }

        # highlight keywords
        $keywords = array('select', 'insert', 'update', 'delete', 'where', 'from', 'limit', 'is', 'null', 'having', 'group by', 'order by', 'asc', 'desc');
        $regexp   = '/\b' . implode('\b|\b', $keywords) . '\b/i';

        $query = preg_replace_callback($regexp, function ($match) {
            return strtoupper($match[0]);
        }, $query);

        return $query;
    }
}