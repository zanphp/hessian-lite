<?php

namespace ZanPHP\HessianLite;

use Exception;

class Stream
{
    public $pos = 0;
    public $len;
    public $bytes;

    public function __construct($data = null)
    {
        if ($data) {
            $this->bytes = $data;
            $this->len = strlen($data);
            $this->pos = 0;
        }
    }

    public function peek($count = 1, $pos = null)
    {
        if ($pos == null) {
            $pos = $this->pos;
        }
        return substr($this->bytes, $pos, $count);
    }

    public function read($count = 1)
    {
        if ($count <= 0) {
            return "";
        }

        $portion = substr($this->bytes, $this->pos, $count);
        $read = strlen($portion);
        $this->pos += $read;
        if ($read < $count) {
            if ($this->pos === 0) {
                throw new StreamEOF('Empty stream received!');
            } else {
                throw new StreamEOF('read past end of stream: ' . $this->pos);
            }
        }
        return $portion;
    }

    public function readAll()
    {
        $this->pos = $this->len;
        return $this->bytes;
    }

    public function write($bytes)
    {
        $this->len += strlen($bytes);
        $this->bytes = $this->bytes . $bytes;
    }

    public function getData()
    {
        return $this->bytes;
    }
}


