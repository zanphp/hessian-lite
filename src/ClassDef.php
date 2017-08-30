<?php

namespace ZanPHP\HessianLite;


/**
 * Hold information on declared classes in the incoming payload
 * @author vsayajin
 */
class ClassDef implements IgnoreCode
{
    public $type;
    public $remoteType;
    public $props = [];
}
