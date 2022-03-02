<?php

namespace MabangSdk\MQ;

use MabangSdk\MQ\ProducerInterface;
use PhpAmqpLib\Message\AMQPMessage;
use PhpAmqpLib\Wire\AMQPTable;
use MabangSdk\MQ\BaseAmqp;
/**
 * Class AbstractProducer
 * @package MabangSdk\MQ\Amqp
 */
abstract class AbstractProducer extends BaseAmqp implements ProducerInterface
{
    /**
     * @param string $messageBody
     * @param array $options
     * @return AMQPMessage
     */
    public function getMessage($messageBody, $options = [])
    {
        $this->setParameter('delivery_mode', AMQPMessage::DELIVERY_MODE_PERSISTENT);

        $message = new AMQPMessage(
            $messageBody,
            $this->getParameters()
        );

        if (!empty($options)) {
            $message->set('application_headers', new AMQPTable($options));
        }

        return $message;
    }

    /**
     * @param string $messageBody
     * @return mixed
     */
    abstract public function publish($messageBody);
}
