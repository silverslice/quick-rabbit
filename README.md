Lightweight queue library for RabbitMQ
============================================================

## Install

`composer require silverslice/quick-rabbit`

## Features
- Push messages with delay
- Individual retry strategy for each job

## Usage

Create Job class:

```php

namespace Silverslice\QuickRabbit\Tests\Jobs;

use Silverslice\QuickRabbit\AbstractJob;

class TestJob extends AbstractJob
{
    public $message;
    
    public function execute()
    {
        echo $this->message . ' ' . date('H:i:s') . "\n";
    }
}
```

Push job to the queue:
```php

use Silverslice\QuickRabbit\Connection;
use Silverslice\QuickRabbit\Queue;
use Silverslice\QuickRabbit\Tests\Jobs\TestJob;

require __DIR__ . '/../vendor/autoload.php';

// set credentials for RabbitMQ
$connection = new Connection();
$connection->user = 'guest';
$connection->password = 'guest';

$queue = new Queue($connection);

$job = new TestJob();
$job->message = 'My first job';

// push to the queue
$queue->push($job);

// push to the queue with delay 2 seconds
$queue->pushWithDelay($job, 2000);

```

Run worker:
```php

use Silverslice\QuickRabbit\Connection;
use Silverslice\QuickRabbit\Worker;

require __DIR__ . '/../vendor/autoload.php';

$connection = new Connection();
$worker = new Worker($connection);

// will be executed if Job failed after max retries
$worker->setFailedCallback(function ($job, Throwable $exception) {
    echo 'FailedCallback: ' . $exception->getMessage() . PHP_EOL;
});
$worker->run();

```

You can set individual retry logic in the job class.
Default behaviour: maximum 5 retries, delay between retries is 
1 second with multiplier 2 (1, 2, 4, 8, 16 seconds).

```php

class TestJob extends AbstractJob
{
    public $message;

    public function execute()
    {
        
    }

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

```

For testing / local development SyncQueue class may be useful.
SyncQueue executes job synchronously:

```php

$queue = new SyncQueue($connection);

$job = new TestJob();
$job->message = 'My first job';

// will be executed synchronously
$queue->push($job);

```