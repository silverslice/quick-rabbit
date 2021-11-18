<?php

namespace Silverslice\QuickRabbit;

use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Wire\AMQPTable;

/**
 * Connection to RabbitMQ
 */
class Connection
{
    public $host = 'localhost';
    public $port = 5672;
    public $user = 'guest';
    public $password = 'guest';
    public $queueName = 'queue';
    public $exchangeName = 'exchange';
    public $delayExchangeName = 'exchange_delay';
    public $vhost = '/';

    /**
     * @var AMQPStreamConnection
     */
    protected $connection;
    /**
     * @var AMQPChannel
     */
    protected $channel;

    public function __construct()
    {
        register_shutdown_function([$this, 'close']);
    }

    public function getChanel(): AMQPChannel
    {
        $this->open();
        return $this->channel;
    }

    /**
     * Opens connection and channel.
     */
    public function open()
    {
        if ($this->channel) {
            return;
        }
        $this->connection = new AMQPStreamConnection($this->host, $this->port, $this->user, $this->password, $this->vhost);
        $this->channel = $this->connection->channel();
        $this->channel->exchange_declare($this->exchangeName, 'direct', false, true, false);
        $this->channel->queue_declare($this->queueName, false, true, false, false);
        $this->channel->queue_bind($this->queueName, $this->exchangeName);
    }

    /**
     * Closes connection and channel.
     */
    public function close()
    {
        if (!$this->channel) {
            return;
        }
        $this->channel->close();
        $this->connection->close();
    }

    /**
     * Declares queue for delays
     * @param int $delayInMs Delay in milliseconds
     */
    public function setupDelayQueue($delayInMs)
    {
        $this->open();

        $name = $this->getRoutingKeyForDelay($delayInMs);
        $this->channel->exchange_declare($this->delayExchangeName, 'direct', false, true, false);
        $this->channel->queue_declare($name, false, true, false, false, false, new AMQPTable([
            'x-dead-letter-exchange' => $this->exchangeName,
            'x-message-ttl' => (int)$delayInMs,
            'x-expires' => (int)$delayInMs + 30000,
            'x-dead-letter-routing-key' => '',
        ]));
        $this->channel->queue_bind($name, $this->delayExchangeName, $name);
    }

    public function getRoutingKeyForDelay($delayInMs)
    {
        return "delay_{$this->exchangeName}_$delayInMs";
    }
}
