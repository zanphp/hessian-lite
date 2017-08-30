<?php

namespace ZanPHP\HessianLite;

/**
 * Represents a parsing rule with a type and a calling function
 * @author vsayajin
 */
class ParsingRule
{
    public $type;
    public $func;
    public $desc;

    public function __construct($type = '', $func = '', $desc = '')
    {
        $this->type = $type;
        $this->func = $func;
        $this->desc = $desc;
    }
}