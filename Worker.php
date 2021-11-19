<?php

namespace Silverslice\QuickRabbit;

use PhpAmqpLib\Message\AMQPMessage;

class Worker
{
    /** @var Connection Connection */
    protected $connection;
    protected $queue;

    protected $shouldExit = false;

    protected $debug = false;

    /** @var callable */
    protected $failedCallback;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
        $this->queue = new Queue($connection);
    }

    public function run()
    {
        $this->debug('Worker started');

        $this->registerSignalHandler();

        $callback = function (AMQPMessage $msg) {
            $this->handle($msg);
        };
        $channel = $this->connection->getChanel();
        $channel->basic_qos(null, 1, null);
        $channel->basic_consume($this->connection->queueName, '', false, false, false, false, $callback);

        while ($channel->is_consuming() && !$this->shouldExit) {
            $channel->wait();
        }
    }

    /**
     * Sets callback for failed jobs.
     * Will be executed if job is not retryable
     *
     * @param callable $callback
     */
    public function setFailedCallback(callable $callback)
    {
        $this->failedCallback = $callback;
    }

    /**
     * Enables or disables debug messages
     *
     * @param $val
     */
    public function setDebug($val)
    {
        $this->debug = $val;
    }

    protected function registerSignalHandler()
    {
        pcntl_async_signals(true);

        foreach ([SIGINT, SIGTERM, SIGHUP] as $sig) {
            pcntl_signal($sig, function () {
                $this->shouldExit = true;
                $this->debug("Worker stopped");
            });
        }
    }

    protected function handle(AMQPMessage $msg)
    {
        $this->debug('Received job: ' . $msg->body);

        /** @var AbstractJob $job */
        $job = unserialize($msg->body);
        $retries = 0;
        $headers = [];
        if ($msg->has('application_headers')) {
            $headers = $msg->get('application_headers');
            $headers = $headers->getNativeData();
        }
        if (isset($headers['retries'])) {
            $retries = $headers['retries'];
        }
        try {
            $this->debug('Execute job, retries=' . $retries);

            $job->execute();
            $msg->ack();

            $this->debug('Job is done');
        } catch (\Throwable $e) {
            $msg->ack();
            $retries = $retries + 1;
            if ($job->isRetryable($retries)) {
                // redeliver
                $delay = $job->getRetryDelay($retries);

                $this->debug("Job failed. Redeliver with delay $delay, retry $retries");
                $this->queue->pushWithDelay($job, $job->getRetryDelay($retries), ['retries' => $retries]);
            } else { // not retryable
                $this->debug('Job failed. Not retryable, reject');

                if ($this->failedCallback) {
                    $func = $this->failedCallback;
                    $func($job, $e);
                }
            }
        }
    }

    protected function debug($msg)
    {
        if ($this->debug) {
            $date = date('d.m.Y H:i:s');
            $pid = getmypid();
            echo "[$date] [$pid] $msg" . PHP_EOL;
        }
    }
}
