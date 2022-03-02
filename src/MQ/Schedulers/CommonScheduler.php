<?php

namespace MabangSdk\MQ\Schedulers;

/**
 * Class CommonScheduler
 * @package MabangSdk\MQ\Schedulers
 */
class CommonScheduler extends AbstractScheduler
{
    /**
     * 根据 handler 获取相应的处理类
     *
     * @param string $handler
     * @return string
     */
    public function formatEventClassName(string $handler) : string
    {
        // handler class
        $msg            = json_decode($handler, true);
        $handlerClass   = $msg['event'] ?? '';

        // namespace 共用队列地址
        $namespace = 'App\\Jobs\\ModulesRabbitMq\\';

        return $namespace . str_replace(['/', '.'], '\\', $handlerClass);
    }

    /**
     * 获取消息主体
     *
     * @param string $msg
     * @return mixed
     */
    public function getMessageBody(string $msg)
    {
        $msg = json_decode($msg, true);

        return $msg['body'] ?? $msg;
    }
}
