<?php
namespace Clockwork\Support\JsonPatch;

use Clockwork\Request\Request;
use Clockwork\Storage\StorageInterface;

/**
 * User: LAHAXE Arnaud
 * Date: 30/09/2015
 * Time: 14:23
 * FileName : Handler.php
 * Project : myo2
 */
class Handler
{
    /**
     * @var Request
     */
    protected $request;

    /**
     * @var StorageInterface
     */
    protected $storage;

    /**
     * Handler constructor.
     *
     * @param \Clockwork\Request\Request $request
     */
    public function __construct(\Clockwork\Request\Request $request, StorageInterface $storage)
    {
        $this->request = $request;
        $this->storage = $storage;
    }

    public function handle(Event $event)
    {
        $this->storage->storePatch($this->request, [
            'op'    => $event->getOperation(),
            'path'  => $event->getPath(),
            'value' => $event->getValue()
        ]);
    }

    /**
     * Register the listeners for the subscriber.
     *
     * @param  \Illuminate\Events\Dispatcher  $events
     * @return void
     */
    public function subscribe($events)
    {
        $events->listen(Event::class, self::class . '@handle');
    }
}