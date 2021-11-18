<?php

namespace Silverslice\QuickRabbit;

/**
 * SyncQueue executes job synchronously
 */
class SyncQueue extends Queue
{
    public function push(AbstractJob $job, $headers = [])
    {
        $job->execute();
    }

    public function pushWithDelay(AbstractJob $job, $delayInMs, $headers = [])
    {
        $this->push($job, $headers);
    }
}
