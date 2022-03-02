<?php

namespace MabangSdk\MQ;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

use MabangSdk\Db\MQ\MQSceneModel;
use MabangSdk\MQ\Consumer;
use MabangSdk\MQ\Producer;
use PhpAmqpLib\Exception\AMQPConnectionClosedException;
use PhpAmqpLib\Message\AMQPMessage;
use PhpAmqpLib\Wire\AMQPTable;

class Amqp
{
    /**
     * 每次循环从队列中获取的消息总数
     *
     * @var int
     */
    const PER_CONSUME_NUMBER    = 100;

    /**
     * 最大的消费重试次数
     *
     * @var int
     */
    const MAX_TRY_COUNT         = 3;

    /**
     * 队列的业务场景缓存键名
     *
     * @var string
     */
    const REDIS_MQ_SCENE_CACHE_KEY = 'mq:scene';

    /**
     * 发送消息（常规队列）
     *
     * @param string $sceneCode
     * @param $body
     * @param int $seconds
     * @throws \Exception
     */
    public function pushNormal(string $sceneCode, $body, $seconds = 0)
    {
        // 格式化
        $message = [
            'body'  => $body,
        ];

        return $this->publish($sceneCode, $message, $seconds);
    }

    /**
     * 发送消息（轻量级公共队列）
     *
     * @param string $event
     * @param $body
     * @throws \Exception
     */
    public function pushCommonLight(string $event, $body)
    {
        // 格式化
        $message = [
            'event' => $event,
            'body'  => $body,
        ];

        return $this->publish('COMMON_LIGHT_MABANG', $message);
    }

    /**
     * 发送消息（重量级公共队列）
     *
     * @param string $event
     * @param $body
     * @throws \Exception
     */
    public function pushCommonWeight(string $event, $body)
    {
        // 格式化
        $message = [
            'event' => $event,
            'body'  => $body,
        ];

        return $this->publish('COMMON_WEIGHT_SRZP', $message);
    }

    /**
     * 发送消息（延迟公共队列）
     *
     * @param string $event
     * @param $body
     * @param int $seconds
     * @throws \Exception
     */
    public function pushCommonDelay(string $event, $body, int $seconds)
    {
        // 格式化
        $message = [
            'event' => $event,
            'body'  => $body,
        ];

        return $this->publish('COMMON_DELAY_SRZP', $message, $seconds);
    }

    /**
     * 消费消息
     *
     * @param string $queue
     * @param callable $callback
     * @param callable|null $failCallback
     * @throws \ErrorException
     */
    public function consume(string $queue, callable $callback, callable $failCallback = null)
    {
        /** @var Consumer $consumer 消费者 */
        $consumer = $this->consumeConnection($queue, $callback, $failCallback);

        // consume
        while (true) {
            try {
                $consumer->consume(self::PER_CONSUME_NUMBER);
            } catch (AMQPConnectionClosedException $e) {
                // fix: Broken pipe or closed connection
                $consumer = static::consumeConnection($queue, $callback, $failCallback);
            }
            usleep(200000);
        }
    }

    /**
     * 声明队列场景配置
     *
     * @return bool
     */
    public function declareSceneConfig() : bool
    {
        $rows = MQSceneModel::query()->get();

        if ($rows) {
            $config = $rows->toArray();

            /** @var Producer $producer */
            $producer   = app(Producer::class);
            $channel    = $producer->getChannel();

            try {
                foreach ($config as $item) {
                    $exchangeType   = pathinfo($item['exchange_name'], PATHINFO_EXTENSION);
                    $arguments      = [];
                    if ($exchangeType == 'delay') {
                        $exchangeType   = 'x-delayed-message';
                        $arguments      = new AMQPTable([
                            'x-delayed-type' => 'direct',
                        ]);
                    } elseif (!in_array($exchangeType, ['fanout', 'direct', 'topic'])) {
                        continue;
                    }

                    // declare exchange
                    $channel->exchange_declare(
                        $item['exchange_name'],
                        $exchangeType,
                        false,
                        true,
                        false,
                        false,
                        false,
                        $arguments
                    );

                    // declare queue
                    $channel->queue_declare(
                        $item['queue_name'],
                        false,
                        true,
                        false,
                        false
                    );

                    // bind
                    $channel->queue_bind(
                        $item['queue_name'],
                        $item['exchange_name'],
                        $item['routing_key']
                    );
                }

                return $this->setSceneCache();
            } catch (\Throwable $t) {
                Log::error('Declare scene config error', [
                    'message'   => $t->getMessage(),
                    'code'      => $t->getCode(),
                    'trace'     => $t->getTrace(),
                    'file'      => $t->getFile(),
                    'line'      => $t->getLine(),
                ]);

                return false;
            }
        }

        return true;
    }

    /**
     * 根据队列名，获取消息业务场景信息
     *
     * @param string $queueName
     * @return mixed
     * @throws \Exception
     */
    public function getSceneInfoByQueue(string $queueName)
    {
        $sceneInfo = $this->getSceneInfo('queue_name', $queueName);

        if (!$sceneInfo) {
            Log::error('mq_scene_not_exists', ['queue_name' => $queueName]);
            throw new \Exception('非法的消息业务场景');
        }

        return $sceneInfo;
    }

    /**
     * 设置队列的业务场景缓存
     *
     * @return bool
     */
    public function setSceneCache() : bool
    {
        $rows       = MQSceneModel::query()->get()->toArray();
        $response   = Redis::setex(self::REDIS_MQ_SCENE_CACHE_KEY, 1800, json_encode($rows, JSON_UNESCAPED_UNICODE));

        return $response ? true : false;
    }

    /**
     * 发送消息
     *
     * @param string $sceneCode
     * @param array $message
     * @param int $seconds
     * @throws \Exception
     */
    public function publish(string $sceneCode, array $message, $seconds = 0)
    {
        $sceneInfo      = $this->getSceneInfoByCode($sceneCode);
        $exchangeType   = pathinfo($sceneInfo['exchange_name'], PATHINFO_EXTENSION);
        if ('delay' != $exchangeType && $seconds) {
            Log::error('message_not_delay', [
                'scene_code'    => $sceneCode,
                'exchange_name' => $sceneInfo['exchange_name'],
                'body'          => $message,
                'seconds'       => $seconds,
            ]);
            throw new \Exception('非延时队列不支持延时消息发送');
        }

        /** @var Producer $producer */
        $producer = app(Producer::class);

        // config
        $producer
            ->setExchangeOptions([
                'name'  => $sceneInfo['exchange_name'],
            ])
            ->setRoutingKey($sceneInfo['routing_key']);

        $channel = $producer->getChannel();
        $channel->confirm_select();

        $message = json_encode($message);

        // set delay
        if ($seconds != 0) {
            $producer->publishDelay($message, $seconds);
        } else {
            $producer->publish($message);
        }

        $channel->set_ack_handler(function (AMQPMessage $m) use ($sceneCode) {
            Log::info('published message: ' . $sceneCode, [
                'msg'   => $m->getBody(),
            ]);
        });

        $channel->set_nack_handler(function (AMQPMessage $m) use ($sceneCode) {
            // log error
            Log::error('publish message failed: ' . $sceneCode, [
                'msg'   => $m->getBody(),
            ]);

            // insert db
        });

        $channel->wait_for_pending_acks();
        $channel->close();
    }

    /**
     * 根据业务场景code，获取消息业务场景信息
     *
     * @param string $sceneCode
     * @return mixed
     * @throws \Exception
     */
    protected function getSceneInfoByCode(string $sceneCode)
    {
        $sceneInfo = $this->getSceneInfo('code', $sceneCode);

        if (!$sceneInfo) {
            Log::error('mq_scene_not_exists', ['code' => $sceneCode]);
            throw new \Exception('非法的消息业务场景');
        }

        return $sceneInfo;
    }

    /**
     * 获取场景配置
     *
     * @param string $field
     * @param string $value
     * @return mixed
     */
    protected function getSceneInfo(string $field, string $value)
    {
        if (!Redis::exists(self::REDIS_MQ_SCENE_CACHE_KEY)) {
            $this->setSceneCache();
        }

        $cache  = Redis::get(self::REDIS_MQ_SCENE_CACHE_KEY);
        $rows   = json_decode($cache, true);

        return collect($rows)->firstWhere($field, $value);
    }

    /**
     * @param string $queue
     * @param callable $callback
     * @param callable|null $failCallback
     * @return Consumer
     * @throws \Exception
     */
    protected function consumeConnection(string $queue, callable $callback, callable $failCallback = null)
    {

        /** @var Consumer $consumer 消费者 */
        $consumer = app(Consumer::class);
        $consumer
            ->setQueueOptions([
                'name'  => $queue,
            ])
            ->setCallback($callback)
            ->setMaxRetries(self::MAX_TRY_COUNT)
            ->setFailCallback($failCallback);

        return $consumer;
    }
}
