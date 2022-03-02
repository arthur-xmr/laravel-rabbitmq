<?php

namespace  MabangSdk\MQ\Schedulers;

use Illuminate\Support\Facades\Log;
use MabangSdk\MQ\Facades\MQ;
use MabangSdk\Db\MQ\MQMsgHandleFailModel;

/**
 * Class AbstractScheduler
 * @package MabangSdk\MQ\Schedulers
 */
abstract class AbstractScheduler
{
    /**
     * @var string 队列名
     */
    protected $queueName;

    /**
     * @var string 处理类名
     */
    protected $handler;

    /**
     * 消息分发
     *
     * @param $queueName
     * @param $handler
     */
    public function run($queueName, $handler = '')
    {
        Log::debug(sprintf('start listening %s queue %s handler ', $queueName, $handler));

        $this->queueName    = $queueName;
        $this->handler      = $handler;
        MQ::consume($queueName, [$this, 'callback'], [$this, 'failCallback']);
    }

    /**
     * @param $msg
     * @return mixed
     */
    public function callback($msg)
    {
        Log::debug(sprintf('-------- start deal %s msg --------', $this->queueName), [
            'msg' => $msg,
        ]);

        try {
            // handler is set to db？
            $handler    = $this->handler ?: $msg;
            $className  = $this->formatEventClassName($handler);
            if (!class_exists($className)) {
                Log::error(sprintf('%s msg handler class not exists', $this->queueName), [
                    'handler'   => $this->handler,
                    'class'     => $className,
                ]);

                return false;
            }

            /** @var IHandler $obj */
            $obj    = new $className;
            $return = $obj->run($this->getMessageBody($msg));
        } catch (\Throwable $e) {
            Log::error(sprintf('deal %s msg error', $this->queueName), [
                'msg'       => $msg,
                'e_file'    => $e->getFile(),
                'e_line'    => $e->getLine(),
                'e_code'    => $e->getCode(),
                'e_msg'     => $e->getMessage(),
                'e_trace'   => $e->getTraceAsString()
            ]);

            $return = false;
        }

        Log::debug(sprintf('-------- end deal %s msg --------', $this->queueName));

        return $return;
    }

    /**
     * @param $msg
     */
    public function failCallBack($msg)
    {
        // insert db
        $data = [
            'queue_name'    => $this->queueName,
            'msg'           => $msg,
        ];
        MQMsgHandleFailModel::query()->insert($data);

        // error report
        Log::error('handle message fail', $data);
    }

    /**
     * 根据 handler 获取相应的处理类
     *
     * @param string $handler
     * @return string
     */
    abstract public function formatEventClassName(string $handler) : string;

    /**
     * 获取消息主体
     *
     * @param string $msg
     * @return mixed
     */
    abstract public function getMessageBody(string $msg);
}
