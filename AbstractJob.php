<?php

namespace Silverslice\QuickRabbit;

abstract class AbstractJob
{
    abstract public function execute();

    /**
     * Is job retryable?
     *
     * @param int $retries Number of retry
     * @return bool
     */
    public function isRetryable($retries): bool
    {
        return $retries <= 5;
    }

    /**
     * Returns retry delay in milliseconds
     *
     * @param $retries
     * @return int
     */
    public function getRetryDelay($retries): int
    {
        return 1000 * 2 ** ($retries - 1);
    }
}
