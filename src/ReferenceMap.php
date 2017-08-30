<?php

namespace ZanPHP\HessianLite;

/**
 * General container for type, class definition and object/array references used in parsers and writers
 * @author vsayajin
 */
class ReferenceMap
{
    public $reflist = [];
    public $typelist = [];
    public $classlist = [];
    public $objectlist = [];

    public function incReference($obj = null)
    {
        $this->reflist[] = new Ref(count($this->objectlist));
        if ($obj != null) {
            $this->objectlist[] = $obj;
        }
    }

    public function getClassIndex($class)
    {
        foreach ($this->classlist as $index => $classdef) {
            if ($classdef->type == $class) {
                return $index;
            }
        }
        return false;
    }

    public function addClassDef(ClassDef $classdef)
    {
        $this->classlist[] = $classdef;
        return count($this->classlist) - 1;
    }

    public function getReference($object)
    {
        return array_search($object, $this->objectlist, true);
    }

    public function getTypeIndex($type)
    {
        return array_search($type, $this->typelist, true);
    }

    public function reset()
    {
        $this->objectlist =
        $this->reflist =
        $this->typelist =
        $this->classlist = [];
    }

}