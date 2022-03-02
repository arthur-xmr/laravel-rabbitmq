<?php

namespace MabangSdk\MQ;

use ErrorException;
use PhpAmqpLib\Message\AMQPMessage;
use PhpAmqpLib\Wire\AMQPTable;

/**
 * Class Consumer
 * @package MabangSdk\MQ\Amqp
 */
class Consumer extends AbstractConsumer
{
    /**
     * @var int
     */
    protected $consumed = 0;

    /**
     * @var int
     */
    private $target;

    /**
     * @var int
     */
    private $maxRetries = 1;

    /**
     * @param int $number
     * @return mixed|void
     * @throws ErrorException
     */
    public function consume($number)
    {
        $this->target = $number;

        // qos
        if (!empty($this->consumerOptions['qos'])) {
            if (!empty($this->consumerOptions['qos'])) {
                $this->channel->basic_qos(
                    $this->consumerOptions['qos']['prefetch_size'],
                    $this->consumerOptions['qos']['prefetch_count'],
                    $this->consumerOptions['qos']['global']
                );
            }
        }

        // consume
        $this->channel->basic_consume(
            $this->queueOptions['name'],
            $this->getConsumerTag(),
            false,
            false,
            false,
            false,
            [$this, 'processMessage']
        );

        // receive
        while (count($this->channel->callbacks)) {
            $this->channel->wait();
        }
    }

    /**
     * @param AMQPMessage $message
     * @throws \Exception
     */
    public function processMessage(AMQPMessage $message)
    {
        try {
            // get retry count
            try {
                /** @var AMQPTable $table */
                $table      = $message->get('application_headers');
                $headers    = $table->getNativeData();
                $retryCount = $headers['x-retry_count'] ?? 0;
            } catch (\Exception $e) {
                $retryCount = 0;
            }

            // ++
            $retryCount++;

            // exec
            try {
                $return = call_user_func($this->callback, $message->body);
            } catch (\Throwable $t) {
                $return = false;
            } catch (\Exception $e) {
                $return = false;
            }

            // return true
            if ($return) {
                $message->delivery_info['channel']
                    ->basic_ack($message->delivery_info['delivery_tag']);

                $this->consumed++;
                if ($this->consumed == $this->target) {
                    $message->delivery_info['channel']
                        ->basic_cancel($message->delivery_info['consumer_tag']);
                }
            }
            // return false
            else {
                if ($retryCount >= $this->maxRetries) {
                    // reject
                    $message->delivery_info['channel']
                        ->basic_reject($message->delivery_info['delivery_tag'], false);

                    // fail callback
                    is_callable($this->failCallback) && call_user_func($this->failCallback, $message->body);
                } else {
                    // update
                    $message->set(
                        'application_headers',
                        ['x-retry_count' => ['I', $retryCount]]
                    );

                    $this->republishMessage($message);
                }
            }
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * @return int
     */
    public function getMaxRetries()
    {
        return $this->maxRetries;
    }

    /**
     * @param integer $maxRetries
     * @return $this
     */
    public function setMaxRetries($maxRetries)
    {
        $this->maxRetries = $maxRetries;

        return $this;
    }

    /**
     * @param AMQPMessage $message
     */
    private function republishMessage(AMQPMessage $message)
    {
        $message->delivery_info['channel']
            ->basic_ack($message->delivery_info['delivery_tag']);
        $message->delivery_info['redelivered'] = 1;

        $this->channel->basic_publish(
            $message,
            $message->delivery_info['exchange'],
            $message->delivery_info['routing_key']
        );
    }
}
