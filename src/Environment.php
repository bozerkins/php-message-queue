<?php
/**
 * Created by PhpStorm.
 * User: bogdans
 * Date: 17.4.3
 * Time: 22:23
 */

namespace MessageStack;


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
        if (!is_dir($queueDir)) {
            if (!@mkdir($queueDir)) {
                throw new \ErrorException(error_get_last());
            }
        }
        $readFile = $this->readFile();
        if (!is_file($readFile)) {
            if (!@touch($readFile)) {
                throw new \ErrorException(error_get_last());
            }
        }
        $readPointerFile = $this->rotateFile();
        if (!is_file($readPointerFile)) {
            if (!@touch($readPointerFile)) {
                throw new \ErrorException(error_get_last());
            }
        }
        $writeFile = $this->writeFile();
        if (!is_file($writeFile)) {
            if (!@touch($writeFile)) {
                throw new \ErrorException(error_get_last());
            }
        }
    }

    public function reset()
    {
        $fp = fopen($this->readFile(), "r+");
        ftruncate($fp, 0);
        fclose($fp);

        $fp = fopen($this->rotateFile(), "r+");
        ftruncate($fp, 0);
        fclose($fp);

        $fp = fopen($this->writeFile(), "r+");
        ftruncate($fp, 0);
        fclose($fp);
    }

    public function remove()
    {
        $dir = $this->options['dir'];
        $readFile = $this->readFile();
        if (is_file($readFile)) {
            if (!@unlink($readFile)) {
                throw new \ErrorException(error_get_last());
            }
        }
        $readPointerFile = $this->rotateFile();
        if (is_file($readPointerFile)) {
            if (!@unlink($readPointerFile)) {
                throw new \ErrorException(error_get_last());
            }
        }
        $writeFile = $this->writeFile();
        if (is_file($writeFile)) {
            if (!@unlink($writeFile)) {
                throw new \ErrorException(error_get_last());
            }
        }
        $queueDir = $this->queueDir();
        if (is_dir($queueDir)) {
            if (!@rmdir($queueDir)) {
                throw new \ErrorException(error_get_last());
            }
        }
    }

    public function validate()
    {
        $queueDir = $this->queueDir();
        if (!is_dir($queueDir)) {
            throw new \ErrorException('Queue Directory not created');
        }
        if (!is_writable($queueDir)) {
            throw new \ErrorException('Queue Directory not writable');
        }
        $readFile = $this->readFile();
        if (!is_file($readFile)) {
            throw new \ErrorException('Read file not created');
        }
        if (!is_writable($readFile)) {
            throw new \ErrorException('Read file not writable');
        }
        $readPointerFile = $this->rotateFile();
        if (!is_file($readPointerFile)) {
            throw new \ErrorException('Read pointer file not created');
        }
        if (!is_writable($readPointerFile)) {
            throw new \ErrorException('Read pointer file not writable');
        }
        $writeFile = $this->writeFile();
        if (!is_file($writeFile)) {
            throw new \ErrorException('Write file not created');
        }
        if (!is_writable($writeFile)) {
            throw new \ErrorException('Write file not writable');
        }
    }
}