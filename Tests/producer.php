<?php

use Silverslice\QuickRabbit\Connection;
use Silverslice\QuickRabbit\Queue;
use Silverslice\QuickRabbit\Tests\Jobs\TestJob;

require __DIR__ . '/../vendor/autoload.php';

$options = getopt('m:d:fn:');
$delay = $options['d'] ?? null;
$n = $options['n'] ?? 1;

if (empty($options['m'])) {
    echo "Usage: php producer.php -m Message [-d Delay] [-f] [-n Number of messages]\n";
    die(1);
}

$connection = new Connection();
$connection->user = 'guest';
$connection->password = 'guest';
$queue = new Queue($connection);

for ($i = 1; $i <= $n; $i++) {
    $job = new TestJob();
    $job->message = $options['m'];
    if ($n > 1) {
        $job->message .= " ($i)";
    }
    if (isset($options['f'])) {
        $job->isFailed = true;
    }

    if ($delay) {
        $queue->pushWithDelay($job, $delay);
    } else {
        $queue->push($job);
    }
}
