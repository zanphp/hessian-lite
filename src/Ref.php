<?php

namespace ZanPHP\HessianLite;


/**
 * Represents an index to a reference. This hack is necessary for handling arrays
 * references
 * @author vsayajin
 */
class Ref
{
    public $index;

    public static function getIndex($list)
    {
        return new Ref($list);
    }

    public function __construct($list)
    {
        if (is_array($list)) {
            $this->index = count($list) - 1;
        } else {
            $this->index = $list;
        }
    }
}