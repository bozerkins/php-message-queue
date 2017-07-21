<?php

namespace MessageQueue;

use MessageQueue\Exception\FileCreateError;
use Symfony\Component\OptionsResolver\OptionsResolver;

class Environment
{
    private $options;

    public function __construct(array $options = [])
    {
        $resolver = new OptionsResolver();
        $this->configureOptions($resolver);
        $this->options = $resolver->resolve($options);
    }

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

    public function dir()
    {
        return rtrim($this->options['dir'], '/');
    }

    public function queueDir()
    {
        return rtrim($this->dir() . '/' . $this->options['queue'], '/');
    }

    public function readFile()
    {
        return $this->queueDir() . '/' . $this->options['read_filename'];
    }

    public function rotateFile()
    {
        return $this->queueDir() . '/' . $this->options['rotate_filename'];
    }

    public function writeFile()
    {
        return $this->queueDir() . '/' . $this->options['write_filename'];
    }

    public function rotateAmount()
    {
        return $this->options['rotate_amount'];
    }

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