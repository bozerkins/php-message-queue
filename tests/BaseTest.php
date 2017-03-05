<?php

/**
 * Created by PhpStorm.
 * User: bogdans
 * Date: 17.4.3
 * Time: 21:43
 */

namespace MessageQueue\Tests;

abstract class BaseTest extends \PHPUnit_Framework_TestCase
{
    protected $dir;
    protected $queue = 'test-queue';

    protected function setUp()
    {
        $this->dir = getenv('PHP_MSTACK_VAR_DIR');
    }

    protected function tearDown()
    {

    }

    protected function makeEnvironment()
    {
        return new \MessageQueue\Environment(
            [
                'dir' => $this->dir,
                'queue' => $this->queue
            ]
        );
    }

    protected function makeQueue(\MessageQueue\Environment $environment)
    {
        return new \MessageQueue\Queue(
            $environment
        );
    }
}
