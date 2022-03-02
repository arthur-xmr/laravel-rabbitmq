<?php

namespace MabangSdk\MQ\Schedulers;

interface IHandler
{
    /**
     * @param mixed $msg
     * @return bool false，将把消息重新放回队列，重试
     * @throws \Throwable 异常或错误，将把消息重新放回队列，重试
     */
    public function run($msg) : bool;
}
