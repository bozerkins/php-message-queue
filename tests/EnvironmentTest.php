<?php

/**
 * Created by PhpStorm.
 * User: bogdans
 * Date: 17.4.3
 * Time: 21:43
 */

namespace MessageStack\Tests;

class EnvironmentTest extends BaseTest
{
    protected function assertMessageStackEnvironmentPresence()
    {
        $this->assertTrue(is_dir($this->dir));
        $this->assertTrue(is_dir($this->dir . '/' . $this->queue));
        $this->assertTrue(is_file($this->dir . '/' . $this->queue . '/' . 'read.txt'));
        $this->assertTrue(is_file($this->dir . '/' . $this->queue . '/' . 'rotate_pointer.txt'));
        $this->assertTrue(is_file($this->dir . '/' . $this->queue . '/' . 'write.txt'));
    }
    protected function assertMessageStackEnvironmentAbsence()
    {
        $this->assertTrue(!is_dir($this->dir . '/' . $this->queue));
        $this->assertTrue(!is_file($this->dir . '/' . $this->queue . '/' . 'read.txt'));
        $this->assertTrue(!is_file($this->dir . '/' . $this->queue . '/' . 'rotate_pointer.txt'));
        $this->assertTrue(!is_file($this->dir . '/' . $this->queue . '/' . 'write.txt'));
    }

    public function testEnvironmentCreation()
    {
        $env = $this->makeEnvironment();
        $env->create();
        $this->assertMessageStackEnvironmentPresence();
    }

    public function testEnvironmentDoubleCreation()
    {
        $env = $this->makeEnvironment();
        $env->create();
        $this->assertMessageStackEnvironmentPresence();
        $env->create();
        $this->assertMessageStackEnvironmentPresence();
    }

    public function testEnvironmentRemoval()
    {
        $env = $this->makeEnvironment();
        $env->create();
        $env->remove();
        $this->assertMessageStackEnvironmentAbsence();
    }

    public function testEnvironmentDoubleRemoval()
    {
        $env = $this->makeEnvironment();
        $env->create();
        $env->remove();
        $this->assertMessageStackEnvironmentAbsence();
        $env->remove();
        $this->assertMessageStackEnvironmentAbsence();
    }

    public function testEnvironmentValidationSuccess()
    {
        $env = $this->makeEnvironment();
        $env->create();
        $env->validate();
    }

    /**
     * @expectedException ErrorException
     */
    public function testEnvironmentValidationCompleteFailure()
    {
        $env = $this->makeEnvironment();
        $env->create();
        $env->remove();
        $env->validate();
    }

    /**
     * @expectedException ErrorException
     */
    public function testEnvironmentValidationPartialFailure()
    {
        $env = $this->makeEnvironment();
        $env->create();
        $file = $this->dir . '/' . $this->queue . '/' . 'rotate_pointer.txt';
        if (is_file($file)) {
            unlink($file);
        }
        $env->validate();
    }

    public function testEnvironmentRecoveryFromPartialFailure()
    {
        $env = $this->makeEnvironment();
        $env->create();
        $file = $this->dir . '/' . $this->queue . '/' . 'rotate_pointer.txt';
        if (is_file($file)) {
            unlink($file);
        }
        $env->create();
        $env->validate();
    }

    public function testEnvironmentPartialRemoval()
    {
        $env = $this->makeEnvironment();
        $env->create();
        $file = $this->dir . '/' . $this->queue . '/' . 'rotate_pointer.txt';
        if (is_file($file)) {
            unlink($file);
        }
        $env->remove();
        $this->assertMessageStackEnvironmentAbsence();
    }
}
