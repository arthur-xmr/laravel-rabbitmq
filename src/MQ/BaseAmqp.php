<?php

namespace MabangSdk\MQ;

use InvalidArgumentException;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AbstractConnection;

/**
 * Class BaseAmqp
 * @package Mq\Amqp
 */
class BaseAmqp
{
    /**
     * @var AbstractConnection
     */
    protected $connection;

    /**
     * @var AMQPChannel
     */
    protected $channel;

    /**
     * @var array
     */
    protected $exchangeOptions = [
        'passive'       => false,
        'durable'       => true,
        'auto_delete'   => false,
        'internal'      => false,
        'nowait'        => false,
        'arguments'     => null,
        'ticket'        => null,
    ];

    /**
     * @var string
     */
    protected $routingKey = '';

    /**
     * @var array
     */
    protected $parameters = [
        'content_type'  => 'text/plain',
    ];

    /**
     * @var array
     */
    protected $consumerOptions = [
        'qos'   => [],
    ];

    /**
     * @var array
     */
    protected $queueOptions = [
        'name'          => '',
        'passive'       => false,
        'durable'       => true,
        'exclusive'     => false,
        'auto_delete'   => false,
        'nowait'        => false,
        'arguments'     => null,
        'ticket'        => null,
    ];

    /**
     * BaseAmqp constructor.
     * @param AbstractConnection $connection
     */
    public function __construct(AbstractConnection $connection)
    {
        $this->connection   = $connection;
        $this->channel      = $connection->channel();
    }

    /**
     * @return AMQPChannel
     */
    public function getChannel()
    {
        return $this->channel;
    }

    /**
     * @param array $options
     * @return $this
     */
    public function setExchangeOptions(array $options)
    {
        if (!isset($options['name'])) {
            throw new InvalidArgumentException(
                'You must provide an exchange name'
            );
        }

        $this->exchangeOptions = array_merge(
            $this->exchangeOptions,
            $options
        );

        return $this;
    }

    /**
     * @param string $routingKey
     * @return $this
     */
    public function setRoutingKey($routingKey)
    {
        $this->routingKey = $routingKey;

        return $this;
    }

    /**
     * @param string $key
     * @param string $value
     * @return $this
     */
    public function setParameter($key, $value)
    {
        $this->parameters[$key] = $value;

        return $this;
    }

    /**
     * @return array
     */
    public function getParameters() : array
    {
        return $this->parameters;
    }

    /**
     * @param array $options
     * @return $this
     */
    public function setQos(array $options)
    {
        $this->consumerOptions['qos'] = array_merge(
            $this->consumerOptions['qos'],
            $options
        );

        return $this;
    }

    /**
     * @param array $options
     * @return $this
     */
    public function setQueueOptions(array $options)
    {
        $this->queueOptions = array_merge(
            $this->queueOptions,
            $options
        );

        return $this;
    }

    /**
     * @return string
     */
    protected function getConsumerTag() : string
    {
        return 'PHPPROCESS_' . getmypid();
    }
}
