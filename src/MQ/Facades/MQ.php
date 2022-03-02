<?php

namespace MabangSdk\MQ\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * Class MQ
 * @package MabangSdk\MQ\Facades
 *
 * @method static consume(string $queue, callable $callback, callable $failCallBack = null)
 * @method static bool declareSceneConfig()
 * @method static getSceneInfoByQueue($queueName)
 * @method static bool setSceneCache()
 * @method static publish(string $sceneCode, array $message, $seconds = 0)
 * @method static pushNormal(string $sceneCode, $body, $seconds = 0)
 * @method static pushCommonLight(string $event, $body)
 * @method static pushCommonWeight(string $event, $body)
 * @method static pushCommonDelay(string $event, $body, int $seconds)
 */
class MQ extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'base.mq';
    }
}
