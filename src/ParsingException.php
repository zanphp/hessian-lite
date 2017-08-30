<?php

namespace ZanPHP\HessianLite;

use Exception;


/**
 * Represents an error while parsing an input stream
 * @author vsayajin
 */
class ParsingException extends Exception
{
    public $position;
    public $details;
}
