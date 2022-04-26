<?php

namespace Silverslice\QuickRabbit;

use PhpAmqpLib\Message\AMQPMessage;
use PhpAmqpLib\Wire\AMQPTable;

class Queue
{
    /** @var Connection Connection */
    protected $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    public function push(AbstractJob $job, $headers = [])
    {
        $this->connection->getChanel()->basic_publish(
            $this->createMessage($job, $headers),
            $this->connection->exchangeName
        );
    }

    public function pushWithDelay(AbstractJob $job, $delayInMs, $headers = [])
    {
        $this->connection->setupDelayQueue($delayInMs);
        $this->connection->getChanel()->basic_publish(
            $this->createMessage($job, $headers),
            $this->connection->delayExchangeName,
            $this->connection->getRoutingKeyForDelay($delayInMs)
        );
    }

    protected function createMessage($job, $headers)
    {
        $body = serialize($job);
        $message = new AMQPMessage($body, [
            'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT,
        ]);
        if ($headers) {
            $message->set('application_headers', new AMQPTable($headers));
        }

        return $message;
    }
}
