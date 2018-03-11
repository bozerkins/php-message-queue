<?php

namespace MessageQueue;

use MessageQueue\Exception\FileCreateError;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Defines and controls the environment in which the Queue is operated
 * @package MessageQueue
 */
class Environment
{
    /**
     * @var array
     */
    private $options;

    /**
     * Environment constructor.
     * @param array $options
     */
    public function __construct(array $options = [])
    {
        $resolver = new OptionsResolver();
        $this->configureOptions($resolver);
        $this->options = $resolver->resolve($options);
    }

    /**
     * Configures and validates basic environment options
     * @param OptionsResolver $resolver
     */
    protected function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setRequired('dir');
        $resolver->setRequired('queue');
        $resolver->setDefaults([
            'read_filename' => 'read.txt',
            'rotate_filename' => 'rotate_pointer.txt',
            'write_filename' => 'write.txt',
            'rotate_amount' => 100
        ]);
    }

    /**
     * @return string root directory for queue operations
     */
    public function dir()
    {
        return rtrim($this->options['dir'], '/');
    }

    /**
     * @return string directory in which all the specific queue operations happen
     */
    public function queueDir()
    {
        return rtrim($this->dir() . '/' . $this->options['queue'], '/');
    }

    /**
     * @return string path to read cache of the queue
     */
    public function readFile()
    {
        return $this->queueDir() . '/' . $this->options['read_filename'];
    }

    /**
     * @return string path to the file, that contains information of write-read cache files rotations
     */
    public function rotateFile()
    {
        return $this->queueDir() . '/' . $this->options['rotate_filename'];
    }

    /**
     * @return string path to the write cache of the queue
     */
    public function writeFile()
    {
        return $this->queueDir() . '/' . $this->options['write_filename'];
    }

    /**
     * @return int amount of records to rotate between write-read cache files per operation
     */
    public function rotateAmount() : int
    {
        return $this->options['rotate_amount'];
    }

    /**
     * create all the necessary directories and files for the Queue to normally operate
     */
    public function create()
    {
        $queueDir = $this->queueDir();
        if (!@mkdir($queueDir, 0775, true) && !is_dir($queueDir)) {
            $error = error_get_last();
            throw new FileCreateError( " {$error['message']}\n {$error['file']}:{$error['line']}", (int)$error['type']);
        }
        $readFile = $this->readFile();
        if (!is_file($readFile) && !@touch($readFile)) {
            $error = error_get_last();
            throw new FileCreateError( " {$error['message']}\n {$error['file']}:{$error['line']}", (int)$error['type']);
        }
        $readPointerFile = $this->rotateFile();
        if (!is_file($readPointerFile) && !@touch($readPointerFile)) {
            $error = error_get_last();
            throw new FileCreateError( " {$error['message']}\n {$error['file']}:{$error['line']}", (int)$error['type']);
        }
        $writeFile = $this->writeFile();
        if (!is_file($writeFile) && !@touch($writeFile)) {
            $error = error_get_last();
            throw new FileCreateError( " {$error['message']}\n {$error['file']}:{$error['line']}", (int)$error['type']);
        }
    }

    /**
     * clear the queue completely and reset everything
     * NOTE: make sure not to call this one by accident
     */
    public function reset()
    {
        $fp = fopen($this->readFile(), "br+");
        ftruncate($fp, 0);
        fclose($fp);

        $fp = fopen($this->rotateFile(), "br+");
        ftruncate($fp, 0);
        fclose($fp);

        $fp = fopen($this->writeFile(), "br+");
        ftruncate($fp, 0);
        fclose($fp);
    }

    /**
     * remove all the environment files and folders (except for the root one)
     * NOTE: make sure not to call this by accident
     */
    public function remove()
    {
        $readFile = $this->readFile();
        if (is_file($readFile) && !@unlink($readFile)) {
            throw new FileCreateError(error_get_last());
        }
        $readPointerFile = $this->rotateFile();
        if (is_file($readPointerFile) && !@unlink($readPointerFile)) {
            throw new FileCreateError(error_get_last());
        }

        $writeFile = $this->writeFile();
        if (is_file($writeFile) && !@unlink($writeFile)) {
            throw new FileCreateError(error_get_last());
        }

        $queueDir = $this->queueDir();
        if (is_dir($queueDir) && !@rmdir($queueDir)) {
            throw new FileCreateError(error_get_last());
        }
    }

    /**
     * Validate that all the necessary environment files and folders are present and operational
     */
    public function validate()
    {
        $queueDir = $this->queueDir();
        if (!is_dir($queueDir)) {
            throw new FileCreateError('Queue Directory not created');
        }
        if (!is_writable($queueDir)) {
            throw new FileCreateError('Queue Directory not writable');
        }
        $readFile = $this->readFile();
        if (!is_file($readFile)) {
            throw new FileCreateError('Read file not created');
        }
        if (!is_writable($readFile)) {
            throw new FileCreateError('Read file not writable');
        }
        $readPointerFile = $this->rotateFile();
        if (!is_file($readPointerFile)) {
            throw new FileCreateError('Read pointer file not created');
        }
        if (!is_writable($readPointerFile)) {
            throw new FileCreateError('Read pointer file not writable');
        }
        $writeFile = $this->writeFile();
        if (!is_file($writeFile)) {
            throw new FileCreateError('Write file not created');
        }
        if (!is_writable($writeFile)) {
            throw new FileCreateError('Write file not writable');
        }
    }
}