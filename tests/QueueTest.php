<?php

/**
 * Created by PhpStorm.
 * User: bogdans
 * Date: 17.4.3
 * Time: 21:43
 */

namespace MessageQueue\Tests;

class QueueTest extends BaseTest
{
    protected function setUp()
    {
        $this->dir = getenv('PHP_MSTACK_VAR_DIR');
        $this->queue .= microtime(true);
        $this->makeEnvironment()->create();
    }

    protected function tearDown()
    {
        $this->makeEnvironment()->remove();
    }

    public function testQueueWrite()
    {
        $env = $this->makeEnvironment();
        $queue = $this->makeQueue($env);
        $message = getmypid() . ':' . microtime(true);
        $queue->write([$message]);
        $this->assertEquals(
            $message . PHP_EOL,
            file_get_contents($env->writeFile())
        );
    }

    public function testQueueRotate()
    {
        $env = $this->makeEnvironment();

        $queue = $this->makeQueue($env);
        $message1 = 'm1' . getmypid() . ':' . microtime(true);
        $queue->write([$message1]);
        $queue->rotate(1);
        $this->assertEquals(
            $message1 . PHP_EOL,
            file_get_contents($env->readFile())
        );
    }

    public function testQueueRotateThreeMessages()
    {
        $env = $this->makeEnvironment();

        $queue = $this->makeQueue($env);
        $message1 = 'm1' . getmypid() . ':' . microtime(true);
        $message2 = 'm2' . getmypid() . ':' . microtime(true);
        $message3 = 'm3' . getmypid() . ':' . microtime(true);
        $queue->write([$message1, $message2, $message3]);
        $queue->rotate(3);
        $this->assertEquals(
            $message3 . PHP_EOL . $message2 . PHP_EOL . $message1 . PHP_EOL ,
            file_get_contents($env->readFile())
        );
    }

    public function testQueueRead()
    {
        $env = $this->makeEnvironment();

        $queue = $this->makeQueue($env);
        $message1 = 'm1' . getmypid() . ':' . microtime(true);
        $message2 = 'm2' . getmypid() . ':' . microtime(true);
        $message3 = 'm3' . getmypid() . ':' . microtime(true);
        $queue->write([$message1, $message2, $message3]);

        $queue->rotate(3);

        $result = $queue->read(1);
        $this->assertTrue(is_array($result));
        $this->assertEquals(1, count($result));
        $this->assertEquals($message1, $result[0]);
    }

    public function testQueueReadMultipleIterations()
    {
        $env = $this->makeEnvironment();

        $queue = $this->makeQueue($env);
        $message1 = 'm1' . getmypid() . ':' . microtime(true);
        $message2 = 'm2' . getmypid() . ':' . microtime(true);
        $message3 = 'm3' . getmypid() . ':' . microtime(true);
        $queue->write([$message1, $message2, $message3]);

        $result = $queue->read(1);
        $this->assertTrue(is_array($result));
        $this->assertEquals(1, count($result));
        $this->assertEquals($message1, $result[0]);

        $result = $queue->read(1);
        $this->assertTrue(is_array($result));
        $this->assertEquals(1, count($result));
        $this->assertEquals($message2, $result[0]);

        $result = $queue->read(1);
        $this->assertTrue(is_array($result));
        $this->assertEquals(1, count($result));
        $this->assertEquals($message3, $result[0]);
    }

    public function testQueueReadMultipleIterationsWithDifferentReadAmounts()
    {
        $env = $this->makeEnvironment();

        $queue = $this->makeQueue($env);
        $message1 = 'm1' . getmypid() . ':' . microtime(true);
        $message2 = 'm2' . getmypid() . ':' . microtime(true);
        $message3 = 'm3' . getmypid() . ':' . microtime(true);
        $message4 = 'm4' . getmypid() . ':' . microtime(true);
        $queue->write([$message1, $message2, $message3, $message4]);

        $result = $queue->read(2);
        $this->assertTrue(is_array($result));
        $this->assertEquals(2, count($result));
        $this->assertEquals($message1, $result[0]);
        $this->assertEquals($message2, $result[1]);

        $result = $queue->read(1);
        $this->assertTrue(is_array($result));
        $this->assertEquals(1, count($result));
        $this->assertEquals($message3, $result[0]);
    }

    public function testQueueMultipleIterations()
    {
        $env = $this->makeEnvironment();

        $queue = $this->makeQueue($env);
        $message1 = 'm1' . getmypid() . ':' . microtime(true);
        $message2 = 'm2' . getmypid() . ':' . microtime(true);
        $queue->write([$message1, $message2]);

        $result = $queue->read(2);
        $this->assertTrue(is_array($result));
        $this->assertEquals(2, count($result));
        $this->assertEquals($message1, $result[0]);
        $this->assertEquals($message2, $result[1]);

        $message3 = 'm3' . getmypid() . ':' . microtime(true);
        $message4 = 'm4' . getmypid() . ':' . microtime(true);
        $queue->write([$message3, $message4]);

        $result = $queue->read(2);
        $this->assertTrue(is_array($result));
        $this->assertEquals(2, count($result));
        $this->assertEquals($message3, $result[0]);
        $this->assertEquals($message4, $result[1]);
    }

    public function testQueueRecycling()
    {
        $env = $this->makeEnvironment();

        $queue = $this->makeQueue($env);
        $message1 = 'm1' . getmypid() . ':' . microtime(true);
        $message2 = 'm2' . getmypid() . ':' . microtime(true);
        $queue->write([$message1, $message2]);

        $result = $queue->read(2);
        $this->assertTrue(is_array($result));
        $this->assertEquals(2, count($result));
        $this->assertEquals($message1, $result[0]);
        $this->assertEquals($message2, $result[1]);

        $message3 = 'm3' . getmypid() . ':' . microtime(true);
        $message4 = 'm4' . getmypid() . ':' . microtime(true);
        $queue->write([$message3, $message4]);

        $result = $queue->read(2);
        $this->assertTrue(is_array($result));
        $this->assertEquals(2, count($result));
        $this->assertEquals($message3, $result[0]);
        $this->assertEquals($message4, $result[1]);

        $message5 = 'm5' . getmypid() . ':' . microtime(true);
        $message6 = 'm6' . getmypid() . ':' . microtime(true);
        $queue->write([$message5, $message6]);

        $queue->recycle();

        $result = $queue->read(2);
        $this->assertTrue(is_array($result));
        $this->assertEquals(2, count($result));
        $this->assertEquals($message5, $result[0]);
        $this->assertEquals($message6, $result[1]);
    }
}
