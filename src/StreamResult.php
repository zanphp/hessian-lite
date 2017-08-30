<?php

namespace ZanPHP\HessianLite;


/**
 * Used by custom write filters to return a stream instead of a modified object
 * @author vsayajin
 */
class StreamResult
{
    public $stream;

    public function __construct($stream)
    {
        $this->stream = $stream;
    }
}