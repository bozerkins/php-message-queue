<?php

/**
 * Created by PhpStorm.
 * User: bogdans
 * Date: 17.4.3
 * Time: 21:43
 */

namespace MessageStack\Tests;

class Test extends \PHPUnit_Framework_TestCase
{
    public function testSmth()
    {
        return;
        dump($this->dir);

        $fname = $this->dir . 'test.txt';

        $f = fopen($fname, 'a');
        flock($f, LOCK_EX);
        for($i = 0; $i < 10000; $i++)
            fwrite($f, getmypid() . ':' . microtime(true) . PHP_EOL);
        flock($f, LOCK_UN);
        fclose($f);
    }

}
