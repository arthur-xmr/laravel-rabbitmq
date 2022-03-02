<?php

namespace MabangSdk\MQ;

use InvalidArgumentException;

/**
 * Class Producer
 * @package MabangSdk\MQ\Amqp
 */
class Producer extends AbstractProducer
{
    /**
     * @param string $messageBody
     * @return mixed|void
     */
    public function publish($messageBody)
    {
        $message = $this->getMessage($messageBody);

        $this->channel->basic_publish(
            $message,
            $this->exchangeOptions['name'],
            $this->routingKey
        );
    }

    /**
     * @param string $messageBody
     * @param int $seconds
     * @return mixed|void
     */
    public function publishDelay($messageBody, $seconds = 0)
    {
        $options = $seconds > 0 ? ['x-delay' => $seconds * 1000] : [];
        $message = $this->getMessage($messageBody, $options);

        $this->channel->basic_publish(
            $message,
            $this->exchangeOptions['name'],
            $this->routingKey
        );
    }

    /**
     * @param array $messageBodies
     */
    public function batchPublish(array $messageBodies)
    {
        foreach ($messageBodies as $key => $messageBody) {
            if (!is_string($messageBody) && !is_int($messageBody)) {
                throw new InvalidArgumentException(
                    'Message body must be string or integer, index: ' . $key
                );
            }
            $message = $this->getMessage($messageBody);

            $this->channel->batch_basic_publish(
                $message,
                $this->exchangeOptions['name'],
                $this->routingKey
            );
        }

        $this->channel->publish_batch();
    }
}
