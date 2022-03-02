<?php

namespace MabangSdk\MQ;

/**
 * Interface ProducerInterface
 * @package  MabangSdk\MQ
 */
interface ProducerInterface
{
    /**
     * @param string $messageBody
     * @return mixed
     */
    public function publish($messageBody);
}
