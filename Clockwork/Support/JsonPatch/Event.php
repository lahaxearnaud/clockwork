<?php
namespace Clockwork\Support\JsonPatch;

/**
 * User: LAHAXE Arnaud
 * Date: 30/09/2015
 * Time: 14:39
 * FileName : Event.php
 * Project : myo2
 */


use Illuminate\Queue\SerializesModels;

class Event extends \App\Events\Event
{
    use SerializesModels;

    protected $path;

    protected $operation;

    protected $value;

    /**
     * Event constructor.
     *
     * @param $path
     * @param $operation
     * @param $value
     */
    public function __construct($operation, $path, $value)
    {
        $this->path      = $path;
        $this->operation = $operation;
        $this->value     = $value;
    }

    /**
     * @return mixed
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * @param mixed $path
     *
     * @return self
     */
    public function setPath($path)
    {
        $this->path = $path;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getOperation()
    {
        return $this->operation;
    }

    /**
     * @param mixed $operation
     *
     * @return self
     */
    public function setOperation($operation)
    {
        $this->operation = $operation;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * @param mixed $value
     *
     * @return self
     */
    public function setValue($value)
    {
        $this->value = $value;

        return $this;
    }
}