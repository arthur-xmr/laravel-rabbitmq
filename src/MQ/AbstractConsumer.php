<?php

namespace MabangSdk\MQ;

use Exception;
use MabangSdk\MQ\ConsumerInterface;

/**
 * Class AbstractConsumer
 * @package  MabangSdk\MQ\Amqp
 */
abstract class AbstractConsumer extends BaseAmqp implements ConsumerInterface
{
    /**
     * @var callable
     */
    protected $callback;

    /**
     * @var callable
     */
    protected $failCallback;

    /**
     * @param callable $callback
     * @return $this
     * @throws Exception
     */
    public function setCallback($callback)
    {
        if (is_callable($callback) === false) {
            throw new Exception("Callback $callback is not callable");
        }

        $this->callback = $callback;

        return $this;
    }

    /**
     * @param callable $callback
     * @return $this
     * @throws Exception
     */
    public function setFailCallback($callback)
    {
        if (is_callable($callback) === false) {
            throw new Exception("Fail callback $callback is not callable");
        }

        $this->failCallback = $callback;

        return $this;
    }

    /**
     * @param int $number
     * @return mixed
     */
    abstract public function consume($number);
}
