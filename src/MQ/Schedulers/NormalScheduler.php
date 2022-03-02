<?php

namespace MabangSdk\MQ\Schedulers;

use Illuminate\Support\Facades\Log;

/**
 * Class NormalScheduler
 * @package MabangSdk\MQ\Schedulers
 */
class NormalScheduler extends AbstractScheduler
{
    /**
     * 根据 handler 获取相应的处理类
     *
     * @param string $handler
     * @return string
     */
    public function formatEventClassName(string $handler) : string
    {
        // 根据数据库配置来直接获取处理类 common.mq_scene.handler
        return $handler;
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
        Log::debug('getMessageBody', [$msg]);
        return $msg['body'] ?? $msg;
    }
}
