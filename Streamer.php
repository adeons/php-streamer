<?php

/**
 * Iterator that uses a callback to fetch values.
 *
 * If the supplied callback sets a value, it will be used as the current value
 * of the iterator. If none is set, it becomes invalid and the loop ends.
 */
class Streamer implements Iterator
{

    /**
     * @var Closure
     */
    private $fetchCallback;

    /**
     * @var Closure
     */
    private $initializeCallback;

    /**
     * @var Closure
     */
    private $disposeCallback;

    /**
     * @var bool
     */
    private $initialized = false;

    /**
     * @var bool
     */
    private $valid = true;

    /**
     * @var null|array|Traversable
     */
    private $chunk;

    /**
     * @var mixed
     */
    private $key;

    /**
     * @var mixed
     */
    private $value;

    /**
     * Constructor.
     *
     * @param Closure $fetchCallback
     * @param Closure $disposeCallback
     * @param Closure $initializeCallback
     */
    public function __construct($fetchCallback, $disposeCallback = null, $initializeCallback = null)
    {
        $this->fetchCallback = $fetchCallback;
        $this->disposeCallback = $disposeCallback;
        $this->initializeCallback = $initializeCallback;
    }

    public function __destruct()
    {
        if ($this->initialized && $this->valid) {
            $this->dispose();
        }
    }

    /**
     * @return Closure
     */
    public function getFetchCallback()
    {
        return $this->fetchCallback;
    }

    /**
     * @param Closure $callback
     */
    public function setFetchCallback($callback)
    {
        $this->fetchCallback = $callback;
    }

    /**
     * Sets the current value and key of this iterator.
     *
     * @param mixed $value
     * @param mixed $key
     */
    public function send($value, $key = null)
    {
        $this->chunk = null;

        $this->value = $value;
        $this->key = $key;

        $this->valid = true;
    }

    /**
     * Stores an array or traversable object from which values and keys for this iterator will be fetched.
     *
     * @param array|Traversable $chunk
     */
    public function sendMany($chunk)
    {
        $this->chunk = $chunk;
        reset($this->chunk);

        $this->key = key($this->chunk);
        $this->value = current($this->chunk);

        $this->valid = true;
    }

    public function current()
    {
        return $this->value;
    }

    public function key()
    {
        return $this->key;
    }

    public function next()
    {
        if ($this->chunk) {

            if (next($this->chunk) === false) {
                $this->fetch();

            } else {
                $this->key = key($this->chunk);
                $this->value = current($this->chunk);
            }

        } else {

            $this->fetch();
        }
    }

    public function rewind()
    {
        if ($this->initialized) {
            throw new BadMethodCallException('Can not rewind after traversal.');
        }

        $this->fetch();
    }

    public function valid()
    {
        return $this->valid;
    }

    protected function initialize()
    {
        $this->initialized = true;

        $callback = $this->initializeCallback;

        if ($callback) {
            $callback($this);
        }
    }

    protected function dispose()
    {
        $callback = $this->disposeCallback;

        if ($callback) {
            $callback($this);
        }
    }

    /**
     * Calls the user provided callback.
     * This iterator will become invalid if no value or iterable is set by the callback.
     */
    protected function fetch()
    {
        if (!$this->valid) {
            return;
        }

        if (!$this->initialized) {
            $this->initialize();
        }

        $this->valid = false;

        $callback = $this->fetchCallback;
        $callback($this);

        if (!$this->valid) {
            $this->dispose();
        }

    }

}
