<?php

namespace MabangSdk\MQ;

/**
 * Interface ConsumerInterface
 * @package  MabangSdk\MQ
 */
interface ConsumerInterface
{
    /**
     * @param callable $callback
     * @return mixed
     */
    public function setCallback($callback);

    /**
     * @param integer $number
     * @return mixed
     */
    public function consume($number);
}
