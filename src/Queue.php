<?php
/**
 * Created by PhpStorm.
 * User: bogdans
 * Date: 17.4.3
 * Time: 22:04
 */

namespace MessageQueue;

class Queue
{
    private $env;

    public function __construct(Environment $env)
    {
        $this->env = $env;
    }

    public function write(array $messages)
    {
        if (!$messages) {
            return;
        }
        $f = fopen($this->env->writeFile(), 'a');
        if(false === $f) {
            throw new \RuntimeException('Unable to open file for write "ab" '.$this->env->writeFile());
        }
        flock($f, LOCK_EX);
        foreach($messages as $message) {
            fwrite($f, (string) $message . PHP_EOL);
        }
        flock($f, LOCK_UN);
        fclose($f);
    }

    public function rotate(int $amount)
    {
        $f = fopen($this->env->rotateFile(), 'r+');

        if(false === $f) {
            throw new \RuntimeException('Unable to rotate file for write "br+" '.$this->env->writeFile());
        }
        flock($f, LOCK_EX);

        $seek = fgets($f);
        if ($seek === false) {
            $seek = 0;
        }


        $f2 = fopen($this->env->writeFile(), 'r');
        if(false === $f2) {
            throw new \RuntimeException('Unable to open file for rotate "br" '.$this->env->writeFile());
        }
        fseek($f2, $seek);

        $lines = [];
        for($i = 0; $i<$amount; $i++) {
            $line = fgets($f2);
            if ($line === false) {
                break;
            }
            $lines[] = $line;
        }

        $f3 = fopen($this->env->readFile(), 'a');
        if(false === $f3) {
            throw new \RuntimeException('Unable to open file for rotate "ba" '.$this->env->writeFile());
        }
        flock($f3, LOCK_EX);

        foreach(array_reverse($lines) as $line) {
            fwrite($f3, $line);
        }

        flock($f3, LOCK_UN);
        fclose($f3);

        fseek($f, 0);
        fwrite($f, ftell($f2));

        fclose($f2);

        flock($f, LOCK_UN);
        fclose($f);
    }

    public function read(int $amount) : array
    {
        $messages = $this->messages($amount);
        $left = $amount - count($messages);
        if ($left > 0) {
            $amount = $left < $this->env->rotateAmount()
                ? $this->env->rotateAmount()
                : $left
            ;
            // TODO: read directly from write file when amount left is more than rotation amount
            $this->rotate($amount);
            $moreMessages = $this->messages($left);
            return array_merge($messages, $moreMessages);
        }
        return $messages;
    }

    public function recycle()
    {
        $f = fopen($this->env->rotateFile(), 'r+');
        if(false === $f) {
            throw new \RuntimeException('Unable to open file for write "ab" '.$this->env->writeFile());
        }
        flock($f, LOCK_EX);

        $seek = fgets($f);
        if ($seek === false) {
            flock($f, LOCK_UN);
            fclose($f);
            return;
        }


        $f2 = fopen($this->env->writeFile(), 'r+');
        if(false === $f2) {
            throw new \RuntimeException('Unable to open file for write "ab" '.$this->env->writeFile());
        }
        flock($f2, LOCK_EX);
        fseek($f2, $seek);

        $readSeek = null;
        $writeSeek = 0;
        while(($line = fgets($f2)) !== false) {

            $readSeek = ftell($f2);

            fseek($f2, $writeSeek);
            fwrite($f2, $line);

            $writeSeek = ftell($f2);
            fseek($f2, $readSeek);
        }

        ftruncate($f2, $writeSeek);
        rewind($f2);

        flock($f2, LOCK_UN);
        fclose($f2);

        ftruncate($f, 0);
        rewind($f);

        flock($f, LOCK_UN);
        fclose($f);
    }

    private function messages(int $amount) : array
    {
        $f = fopen($this->env->readFile(), 'r+');
        if(false === $f) {
            throw new \RuntimeException('Unable to open file for messages "br+" '.$this->env->writeFile());
        }
        flock($f, LOCK_EX);

        $pos = -1;
        $c = null;
        $line = '';
        $lines = [];

        while(count($lines) < $amount) {
            fseek($f, $pos--, SEEK_END);
            $c = fgetc($f);
            if ($c === false) {
                if ($line) {
                    $lines[] = $line;
                }
                break;
            }
            if ($c === PHP_EOL) {
                if ($line) {
                    $lines[] = $line;
                }
                $line = '';
                continue;
            }

            $line .= $c;
        }

        if ($c === false) {
            ftruncate($f, 0);
        } else {
            ftruncate($f, ftell($f));
        }


        flock($f, LOCK_UN);
        fclose($f);

        return array_map(
            function($line) {
                return strrev($line);
            },
            $lines
        );
    }
}
