<?php

namespace MabangSdk\MQ;

class MqConstant
{
    /**
     * 每次循环从队列中获取的消息总数
     *
     * @var int
     */
    const PER_CONSUME_NUMBER = 100;

    /**
     * 最大的消费重试次数
     *
     * @var int
     */
    const MAX_TRY_COUNT = 3;

    /**
     * 业务场景code
     *
     * @var string
     */

    const TEST_SCENE_CODE                        = 'TEST_ABC_QUEUE_CODE';
    const TEST_DELAY_SCENE_CODE                  = 'TEST_DELAY';

}
