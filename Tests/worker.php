<?php

use Silverslice\QuickRabbit\Connection;
use Silverslice\QuickRabbit\Worker;

require __DIR__ . '/../vendor/autoload.php';

$connection = new Connection();
$worker = new Worker($connection);
$worker->setDebug(true);
$worker->setFailedCallback(function ($job, Throwable $exception) {
    echo 'FailedCallback: ' . $exception->getMessage() . PHP_EOL;
});
$worker->run();
