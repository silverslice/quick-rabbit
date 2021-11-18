<?php

namespace Silverslice\QuickRabbit\Tests\Jobs;

use Silverslice\QuickRabbit\AbstractJob;

class TestJob extends AbstractJob
{
    public $message;
    public $isFailed = false;

    public function execute()
    {
        $this->realSleep(2);
        if ($this->isFailed) {
            throw new \Exception('Demo job not available');
        } else {
            echo $this->message . ' ' . date('H:i:s') . "\n";
        }
    }

    public function isRetryable($retries): bool
    {
        return $retries <= 5;
    }

    public function getRetryDelay($retries): int
    {
        return 1000 * 2 ** ($retries - 1);
    }

    protected function realSleep(int $seconds): bool
    {
        $period = ['seconds' => $seconds, 'nanoseconds' => 0];

        while (array_sum($period) > 0) {
            $period = time_nanosleep($period['seconds'], $period['nanoseconds']);

            if (is_bool($period)) {
                return $period;
            }
        }

        return true;
    }
}
