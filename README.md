# PHP Message Queue
A simple php implementation of message queue (or message queue) which 
can write messages and read messages in a FIFO manner. 

[![Build Status](https://travis-ci.org/bozerkins/php-message-queue.svg?branch=master)](https://travis-ci.org/bozerkins/php-message-queue)

## Installation
```sh
composer require bozerkins/php-message-queue
```

## Creating a queue
To define a queue you first need to define some environment configurations.
An environment of a queue messaging system defined what folders will be used for operations.
```php
$queue = new \MessageQueue\Queue(
    new \MessageQueue\Environment(
        [
            'dir' => '/var/my-queue-folder',
            'queue' => 'my-queue'
        ]
    )
);
```

You can write to queue and read from queue. These are the basic operations.

> NOTE that you can use this with multiple processes. The Message Queue uses flock(), so there shouldn't be a problem.

```php
# write two messages
$queue->write(
    [
        'my message', 
        'my second message'
    ]
);

# read two messages
print_r($queue->read(2));
```

For several optimization reasons the queue does not delete messages by itself.
To free some disk space from already read messages just run this command from time to time.
Please note that this operation is disk write heavy (when lot's of messages are being passed)
```php
$queue->recycle();
```

To optimize reads from the queue the library uses a caching construction. 
Messages are added into file system based cache in chunks, by default single chunk size is 100 messages.
If you intend to read more than 100 messages from the queue at once please change the configuration option 'rotate_amount' to a bigger number.
```php
$queue = new \MessageQueue\Queue(
    new \MessageQueue\Environment(
        [
            'dir' => '/var/my-queue-folder',
            'queue' => 'my-queue',
            'rotate_amount' => 200
        ]
    )
);
```

## Contacts
If you wish to improve the library, feel free to submit merge request or contact me at b.ozerkins@gmail.com
